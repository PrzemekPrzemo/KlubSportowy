<?php

declare(strict_types=1);

namespace App\Helpers\Ksef;

use App\Helpers\Database;
use App\Helpers\Encryption;
use RuntimeException;

/**
 * XAdES-BES signer dla KSeF — Phase 3.
 *
 * Generuje podpisany XML wymagany przez `POST /online/Session/InitSigned`:
 * wraz z challenge zwroconym przez `POST /online/Session/AuthorisationChallenge`
 * pakuje SessionToken (XML) i dolacza enveloped XAdES-BES signature
 * (RSA-SHA256 + sha256 digest, certyfikat X.509 z .p12 klubu).
 *
 * BEZPIECZENSTWO:
 *   - cert path + zaszyfrowane haslo ladujemy ZAWSZE z club_ksef_config
 *     (WHERE club_id = ?) — nigdy z parametru.
 *   - haslo deszyfrujemy tylko na czas openssl_pkcs12_read i zaraz zerujemy.
 *   - private key NIE jest persistowany na dysku poza .p12 — uzywamy go
 *     w pamieci i wyzerujemy zmienne przed return.
 *   - W razie wyjatku NIGDY nie logujemy plaintext password ani PEM key.
 *
 * UWAGA — to MVP poziomu "test-only" XAdES-BES. Produkcyjna integracja
 * z KSeF prawdopodobnie wymaga dodatkowych elementow (timestamp via TSA,
 * canonicalization c14n bardziej rygorystyczny, walidacja certyfikatu
 * przez listy CRL/OCSP). Pre-prod, test na ksef-test.mf.gov.pl OK.
 */
final class XAdESSigner
{
    public const DS_NS    = 'http://www.w3.org/2000/09/xmldsig#';
    public const XADES_NS = 'http://uri.etsi.org/01903/v1.3.2#';

    /**
     * Podpisuje SessionToken XML (z osadzonym challenge) wedlug XAdES-BES.
     *
     * @param string $challengeBase64 wartosc 'challenge' z KsefApiClient::authChallenge()
     * @param string $nip             NIP klubu (10 cyfr) — referowany w SessionToken
     * @param int    $clubId          do odszyfrowania cert + password z DB
     *
     * @return string podpisany XAdES-BES XML, gotowy do POST /online/Session/InitSigned
     */
    public function signChallenge(string $challengeBase64, string $nip, int $clubId): string
    {
        $nip = preg_replace('/\D/', '', $nip) ?? '';
        if (strlen($nip) !== 10) {
            throw new RuntimeException('XAdESSigner: invalid NIP');
        }
        if ($challengeBase64 === '') {
            throw new RuntimeException('XAdESSigner: empty challenge');
        }

        [$certPem, $privateKey] = $this->loadCertificate($clubId);

        try {
            $unsignedXml = $this->buildUnsignedSessionTokenXml($challengeBase64, $nip);
            $signedXml   = $this->envelopeSign($unsignedXml, $certPem, $privateKey);
            return $signedXml;
        } finally {
            // Zerowanie sekretow (best-effort — PHP nie gwarantuje "wipe",
            // ale unset() pomaga GC i nie zostawia ich w stack-trace).
            if (is_resource($privateKey) || $privateKey instanceof \OpenSSLAsymmetricKey) {
                @openssl_free_key($privateKey); // no-op for >= 8.0 OpenSSLAsymmetricKey, but safe
            }
            unset($privateKey, $certPem);
        }
    }

    // ── Internal: PKCS#12 loader ─────────────────────────────────

    /**
     * Laduje .p12 / .pfx z dysku (sciezka z club_ksef_config.cert_path),
     * deszyfruje haslo i zwraca [PEM cert, PEM private key resource].
     *
     * @return array{0:string,1:\OpenSSLAsymmetricKey|resource}
     */
    private function loadCertificate(int $clubId): array
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare(
            "SELECT cert_path, cert_password_encrypted
               FROM club_ksef_config
              WHERE club_id = ? LIMIT 1"
        );
        $st->execute([$clubId]);
        $row = $st->fetch();
        if (!$row) {
            throw new RuntimeException('XAdESSigner: brak konfiguracji KSeF dla klubu.');
        }
        $certPath = (string)($row['cert_path'] ?? '');
        if ($certPath === '' || !is_readable($certPath)) {
            throw new RuntimeException('XAdESSigner: cert_path nieprawidlowy lub niedostepny.');
        }

        // Sanity-check sciezki — wymagamy zeby cert byl w storage/ksef/{club_id}/...
        // Zapobiega path traversal jesli administrator klubu mogl ustawic cert_path.
        $expectedPrefix = ROOT_PATH . '/storage/ksef/' . $clubId . '/';
        $realPath       = realpath($certPath);
        $realPrefix     = realpath(ROOT_PATH . '/storage/ksef/' . $clubId);
        if ($realPath === false || $realPrefix === false || !str_starts_with($realPath, $realPrefix)) {
            throw new RuntimeException('XAdESSigner: cert_path poza dozwolonym katalogiem.');
        }

        $blob = @file_get_contents($realPath);
        if ($blob === false || $blob === '') {
            throw new RuntimeException('XAdESSigner: pusty plik certyfikatu.');
        }

        $password = Encryption::decryptForClub((string)$row['cert_password_encrypted'], $clubId) ?? '';

        $store = [];
        $ok    = openssl_pkcs12_read($blob, $store, $password);
        // wipe password ASAP
        $password = str_repeat("\0", strlen($password));
        unset($password);

        if (!$ok || !isset($store['cert'], $store['pkey'])) {
            throw new RuntimeException('XAdESSigner: nie udalo sie odczytac .p12 (zle haslo?).');
        }

        $pkey = openssl_pkey_get_private($store['pkey']);
        if ($pkey === false) {
            throw new RuntimeException('XAdESSigner: nieprawidlowy klucz prywatny w .p12.');
        }
        return [(string)$store['cert'], $pkey];
    }

    // ── Internal: XML construction ────────────────────────────────

    /**
     * Buduje (nie-podpisany) XML SessionToken (przed dolozeniem ds:Signature).
     */
    private function buildUnsignedSessionTokenXml(string $challengeBase64, string $nip): string
    {
        // Zgodnie z KSeF-wymaganym schematem:
        // <ns3:InitSessionTokenRequest xmlns:ns2="..." xmlns:ns3="...">
        //   <ns3:Context>
        //     <Challenge>...</Challenge>
        //     <Identifier><ns2:Identifier>NIP</ns2:Identifier></Identifier>
        //     <DocumentType>...</DocumentType>
        //     <Token>...</Token>
        //   </ns3:Context>
        // </ns3:InitSessionTokenRequest>
        $tokenXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<ns3:InitSessionTokenRequest'
            . ' xmlns:ns2="http://ksef.mf.gov.pl/schema/gtw/svc/types/2021/10/01/0001"'
            . ' xmlns:ns3="http://ksef.mf.gov.pl/schema/gtw/svc/online/auth/request/2021/10/01/0001">'
            . '<ns3:Context>'
            . '<Challenge>' . htmlspecialchars($challengeBase64, ENT_XML1) . '</Challenge>'
            . '<Identifier xsi:type="ns2:SubjectIdentifierByCompanyType"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<ns2:Identifier>' . htmlspecialchars($nip, ENT_XML1) . '</ns2:Identifier>'
            . '</Identifier>'
            . '<DocumentType>'
            . '<ns2:Service>KSeF</ns2:Service>'
            . '<ns2:FormCode>'
            . '<ns2:SystemCode>FA (2)</ns2:SystemCode>'
            . '<ns2:SchemaVersion>1-0E</ns2:SchemaVersion>'
            . '<ns2:TargetNamespace>http://crd.gov.pl/wzor/2023/06/29/12648/</ns2:TargetNamespace>'
            . '<ns2:Value>FA</ns2:Value>'
            . '</ns2:FormCode>'
            . '</DocumentType>'
            . '<Token>' . htmlspecialchars(bin2hex(random_bytes(16)), ENT_XML1) . '</Token>'
            . '</ns3:Context>'
            . '</ns3:InitSessionTokenRequest>';

        return $tokenXml;
    }

    /**
     * Doklada XAdES-BES enveloped signature do wskazanego XML.
     *
     * @param \OpenSSLAsymmetricKey|resource $privateKey
     */
    private function envelopeSign(string $xml, string $certPem, $privateKey): string
    {
        // 1) Wylicz digest dokumentu (sha256, base64) — Reference URI="" =
        // calosc dokumentu z enveloped-signature transform.
        $docCanonical = $this->canonicalize($xml);
        $docDigest    = base64_encode(hash('sha256', $docCanonical, true));

        // 2) Cert digest do XAdES:SigningCertificate
        $certPemNorm = trim($certPem);
        $certDer     = $this->pemToDer($certPemNorm);
        $certDigest  = base64_encode(hash('sha256', $certDer, true));

        // 3) Issuer + serial z certyfikatu
        $parsed = openssl_x509_parse($certPemNorm);
        if ($parsed === false) {
            throw new RuntimeException('XAdESSigner: cannot parse certificate.');
        }
        $issuer       = $this->formatIssuer($parsed['issuer'] ?? []);
        $serialNumber = (string)($parsed['serialNumber'] ?? '0');

        // 4) Zbuduj SignedProperties (XAdES)
        $signedPropsId = 'SignedProperties-' . bin2hex(random_bytes(4));
        $signatureId   = 'Signature-' . bin2hex(random_bytes(4));
        $signingTime   = gmdate('Y-m-d\TH:i:s\Z');

        $signedProps = '<xades:SignedProperties'
            . ' xmlns:xades="' . self::XADES_NS . '"'
            . ' Id="' . $signedPropsId . '">'
            . '<xades:SignedSignatureProperties>'
            . '<xades:SigningTime>' . $signingTime . '</xades:SigningTime>'
            . '<xades:SigningCertificate>'
            . '<xades:Cert>'
            . '<xades:CertDigest>'
            . '<ds:DigestMethod xmlns:ds="' . self::DS_NS . '" Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'
            . '<ds:DigestValue xmlns:ds="' . self::DS_NS . '">' . $certDigest . '</ds:DigestValue>'
            . '</xades:CertDigest>'
            . '<xades:IssuerSerial>'
            . '<ds:X509IssuerName xmlns:ds="' . self::DS_NS . '">' . htmlspecialchars($issuer, ENT_XML1) . '</ds:X509IssuerName>'
            . '<ds:X509SerialNumber xmlns:ds="' . self::DS_NS . '">' . htmlspecialchars($serialNumber, ENT_XML1) . '</ds:X509SerialNumber>'
            . '</xades:IssuerSerial>'
            . '</xades:Cert>'
            . '</xades:SigningCertificate>'
            . '</xades:SignedSignatureProperties>'
            . '</xades:SignedProperties>';

        $signedPropsCanonical = $this->canonicalize($signedProps);
        $signedPropsDigest    = base64_encode(hash('sha256', $signedPropsCanonical, true));

        // 5) Zbuduj SignedInfo
        $signedInfo = '<ds:SignedInfo xmlns:ds="' . self::DS_NS . '">'
            . '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>'
            . '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>'
            . '<ds:Reference URI="">'
            . '<ds:Transforms>'
            . '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>'
            . '</ds:Transforms>'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'
            . '<ds:DigestValue>' . $docDigest . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '<ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="#' . $signedPropsId . '">'
            . '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>'
            . '<ds:DigestValue>' . $signedPropsDigest . '</ds:DigestValue>'
            . '</ds:Reference>'
            . '</ds:SignedInfo>';

        // 6) Sign canonicalized SignedInfo
        $signedInfoCanonical = $this->canonicalize($signedInfo);
        $signatureValue      = '';
        $ok = openssl_sign($signedInfoCanonical, $signatureValue, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok || $signatureValue === '') {
            throw new RuntimeException('XAdESSigner: openssl_sign failed.');
        }
        $signatureValueB64 = base64_encode($signatureValue);
        unset($signatureValue);

        // 7) Cert dla KeyInfo (PEM bez header/footer → base64 z DER)
        $certBase64 = base64_encode($certDer);

        // 8) Zbuduj kompletny ds:Signature
        $dsSignature = '<ds:Signature'
            . ' xmlns:ds="' . self::DS_NS . '"'
            . ' Id="' . $signatureId . '">'
            . $signedInfo
            . '<ds:SignatureValue>' . $signatureValueB64 . '</ds:SignatureValue>'
            . '<ds:KeyInfo>'
            . '<ds:X509Data>'
            . '<ds:X509Certificate>' . $certBase64 . '</ds:X509Certificate>'
            . '</ds:X509Data>'
            . '</ds:KeyInfo>'
            . '<ds:Object>'
            . '<xades:QualifyingProperties'
            . ' xmlns:xades="' . self::XADES_NS . '"'
            . ' Target="#' . $signatureId . '">'
            . $signedProps
            . '</xades:QualifyingProperties>'
            . '</ds:Object>'
            . '</ds:Signature>';

        // 9) Wlasciwa enveloped: wstaw Signature przed </ns3:InitSessionTokenRequest>
        $closingTag = '</ns3:InitSessionTokenRequest>';
        if (strpos($xml, $closingTag) === false) {
            throw new RuntimeException('XAdESSigner: missing closing tag for envelope.');
        }
        $injected = str_replace($closingTag, $dsSignature . $closingTag, $xml);

        return $injected;
    }

    // ── Internal: helpers ────────────────────────────────────────

    /**
     * Canonical XML (Exclusive c14n, no comments) via DOMDocument::C14N().
     * Fallback do trim() jesli DOM zawodzi (defensywne — KSeF moze nie
     * akceptowac, ale wtedy roznica jest debuggable).
     */
    private function canonicalize(string $xml): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput       = false;

        $prev = libxml_use_internal_errors(true);
        try {
            if (!$doc->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                // Surowy fallback — np. fragment (SignedInfo bez deklaracji)
                $wrapped = '<?xml version="1.0" encoding="UTF-8"?><wrap>' . $xml . '</wrap>';
                if (!$doc->loadXML($wrapped, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                    return $xml;
                }
                $c14n = $doc->documentElement?->C14N(false, false);
                if (!is_string($c14n)) {
                    return $xml;
                }
                // Usun wrapper
                return preg_replace('/^<wrap[^>]*>|<\/wrap>$/', '', $c14n) ?? $xml;
            }
            $c14n = $doc->C14N(false, false);
            return is_string($c14n) ? $c14n : $xml;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
        }
    }

    /**
     * Konwertuje PEM (-----BEGIN CERTIFICATE----- ... -----END CERTIFICATE-----)
     * na surowy DER (binary).
     */
    private function pemToDer(string $pem): string
    {
        $stripped = preg_replace('/-----(BEGIN|END)[^-]+-----/', '', $pem) ?? '';
        $stripped = preg_replace('/\s+/', '', $stripped) ?? '';
        $der = base64_decode($stripped, true);
        if ($der === false || $der === '') {
            throw new RuntimeException('XAdESSigner: malformed PEM certificate.');
        }
        return $der;
    }

    /**
     * Issuer DN w formacie RFC2253 (CN=...,O=...,C=PL). openssl_x509_parse
     * zwraca asoc-array, kolejnosc nie zachowana — uzywamy stalej kolejnosci.
     *
     * @param array<string,mixed> $issuer
     */
    private function formatIssuer(array $issuer): string
    {
        $order = ['CN', 'OU', 'O', 'L', 'ST', 'C', 'emailAddress'];
        $parts = [];
        foreach ($order as $k) {
            if (!empty($issuer[$k])) {
                $val = is_array($issuer[$k]) ? implode('+', $issuer[$k]) : (string)$issuer[$k];
                $parts[] = $k . '=' . $val;
            }
        }
        return implode(',', $parts);
    }
}
