/**
 * Interaktywny edytor składu meczowego (drag & drop na boisku).
 *
 * Użycie:
 *   <div id="lineup-editor"
 *        data-sport="football"
 *        data-match-id="123"
 *        data-team-id="1"
 *        data-members='[{"id":1,"name":"Kowalski Jan","number":7}, ...]'
 *        data-lineup='[{"member_id":1,"position":"NA","x":75,"y":20}, ...]'
 *        data-save-url="/football/matches/123/lineup-save">
 *   </div>
 *
 * Supports: football (pitch), basketball (court), volleyball (court).
 */
(function() {
    'use strict';

    const FIELDS = {
        football: {
            width: 680, height: 440,
            bg: '#2d8a4e', lineColor: '#fff', lineWidth: 2,
            positions: [
                {key:'BR', label:'Bramkarz', x:50, y:220},
                {key:'LO', label:'Lewy obrońca', x:150, y:80},
                {key:'SO', label:'Środk. obrońca L', x:150, y:180},
                {key:'SO2',label:'Środk. obrońca P', x:150, y:260},
                {key:'PO', label:'Prawy obrońca', x:150, y:360},
                {key:'PM', label:'Pomocnik L', x:320, y:120},
                {key:'SR', label:'Środkowy pomoc.', x:320, y:220},
                {key:'PM2',label:'Pomocnik P', x:320, y:320},
                {key:'LS', label:'Lewy skrzydłowy', x:500, y:100},
                {key:'NA', label:'Napastnik', x:500, y:220},
                {key:'PS', label:'Prawy skrzydłowy', x:500, y:340},
            ]
        },
        basketball: {
            width: 600, height: 360,
            bg: '#c4844d', lineColor: '#fff', lineWidth: 2,
            positions: [
                {key:'PG', label:'Rozgrywający', x:300, y:300},
                {key:'SG', label:'Rzucający obrońca', x:150, y:240},
                {key:'SF', label:'Niski skrzydłowy', x:450, y:240},
                {key:'PF', label:'Silny skrzydłowy', x:180, y:120},
                {key:'C',  label:'Środkowy', x:300, y:80},
            ]
        },
        volleyball: {
            width: 500, height: 400,
            bg: '#d4a06a', lineColor: '#fff', lineWidth: 2,
            positions: [
                {key:'4', label:'Atakujący (4)', x:80, y:100},
                {key:'3', label:'Środkowy (3)', x:250, y:80},
                {key:'2', label:'Rozgrywający (2)', x:420, y:100},
                {key:'5', label:'Przyjmujący (5)', x:80, y:280},
                {key:'6', label:'Libero (6)', x:250, y:300},
                {key:'1', label:'Przyjmujący (1)', x:420, y:280},
            ]
        }
    };

    function init() {
        const container = document.getElementById('lineup-editor');
        if (!container) return;

        const sport    = container.dataset.sport || 'football';
        const matchId  = container.dataset.matchId;
        const teamId   = container.dataset.teamId;
        const members  = JSON.parse(container.dataset.members || '[]');
        const existing = JSON.parse(container.dataset.lineup || '[]');
        const saveUrl  = container.dataset.saveUrl;
        const csrfToken = container.dataset.csrf || '';

        const field = FIELDS[sport];
        if (!field) { container.innerHTML = '<p>Nieobsługiwany sport: ' + sport + '</p>'; return; }

        // Canvas for field
        const canvas = document.createElement('canvas');
        canvas.width = field.width;
        canvas.height = field.height;
        canvas.style.border = '2px solid #333';
        canvas.style.borderRadius = '8px';
        canvas.style.cursor = 'pointer';
        container.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        const assigned = {}; // position_key -> member

        // Load existing lineup
        existing.forEach(function(l) {
            const pos = field.positions.find(p => p.key === l.position);
            if (pos) assigned[pos.key] = { id: l.member_id, name: l.name || '?', number: l.jersey_number || '' };
        });

        // Available players list
        const listDiv = document.createElement('div');
        listDiv.className = 'mt-3';
        listDiv.innerHTML = '<h6>Dostępni zawodnicy <small class="text-muted">(kliknij pozycję na boisku, potem zawodnika)</small></h6>';
        const playerList = document.createElement('div');
        playerList.className = 'd-flex flex-wrap gap-1';
        members.forEach(function(m) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-secondary lineup-player-btn';
            btn.textContent = (m.number ? '#' + m.number + ' ' : '') + m.name;
            btn.dataset.memberId = m.id;
            btn.dataset.memberName = m.name;
            btn.dataset.memberNumber = m.number || '';
            btn.addEventListener('click', function() { assignPlayer(m); });
            playerList.appendChild(btn);
        });
        listDiv.appendChild(playerList);
        container.appendChild(listDiv);

        // Save button
        if (saveUrl) {
            const saveBtn = document.createElement('button');
            saveBtn.className = 'btn btn-primary mt-3';
            saveBtn.innerHTML = '<i class="bi bi-check2"></i> Zapisz skład';
            saveBtn.addEventListener('click', function() { saveLineup(saveUrl, csrfToken, matchId, teamId); });
            container.appendChild(saveBtn);
        }

        let selectedPosition = null;

        canvas.addEventListener('click', function(e) {
            const rect = canvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            // Find clicked position
            let clicked = null;
            field.positions.forEach(function(pos) {
                const dx = x - pos.x, dy = y - pos.y;
                if (Math.sqrt(dx*dx + dy*dy) < 25) clicked = pos;
            });

            if (clicked) {
                selectedPosition = clicked;
                draw();
                // Highlight selected
                ctx.strokeStyle = '#ff0';
                ctx.lineWidth = 3;
                ctx.beginPath();
                ctx.arc(clicked.x, clicked.y, 28, 0, Math.PI * 2);
                ctx.stroke();
            }
        });

        function assignPlayer(member) {
            if (!selectedPosition) { alert('Najpierw kliknij pozycję na boisku.'); return; }
            // Remove from previous position if assigned
            Object.keys(assigned).forEach(function(k) {
                if (assigned[k] && assigned[k].id === member.id) delete assigned[k];
            });
            assigned[selectedPosition.key] = { id: member.id, name: member.name, number: member.number || '' };
            selectedPosition = null;
            draw();
        }

        function draw() {
            // Background
            ctx.fillStyle = field.bg;
            ctx.fillRect(0, 0, field.width, field.height);

            // Field lines (simplified)
            ctx.strokeStyle = field.lineColor;
            ctx.lineWidth = field.lineWidth;
            ctx.strokeRect(10, 10, field.width - 20, field.height - 20);
            ctx.beginPath();
            ctx.moveTo(field.width / 2, 10);
            ctx.lineTo(field.width / 2, field.height - 10);
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(field.width / 2, field.height / 2, 50, 0, Math.PI * 2);
            ctx.stroke();

            // Position markers
            field.positions.forEach(function(pos) {
                const player = assigned[pos.key];
                ctx.beginPath();
                ctx.arc(pos.x, pos.y, 22, 0, Math.PI * 2);
                ctx.fillStyle = player ? 'rgba(0,100,255,0.85)' : 'rgba(255,255,255,0.3)';
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 2;
                ctx.stroke();

                // Label
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 11px sans-serif';
                ctx.textAlign = 'center';
                if (player) {
                    ctx.fillText(player.number ? '#' + player.number : '', pos.x, pos.y - 4);
                    ctx.font = '9px sans-serif';
                    ctx.fillText(player.name.split(' ')[0], pos.x, pos.y + 10);
                } else {
                    ctx.fillText(pos.key, pos.x, pos.y + 4);
                }

                // Position label below
                ctx.font = '8px sans-serif';
                ctx.fillStyle = 'rgba(255,255,255,0.6)';
                ctx.fillText(pos.label, pos.x, pos.y + 35);
            });
        }

        function saveLineup(url, csrf, matchId, teamId) {
            const data = [];
            Object.keys(assigned).forEach(function(posKey) {
                const p = assigned[posKey];
                if (p) data.push({ member_id: p.id, position: posKey, jersey_number: p.number });
            });

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.style.display = 'none';

            const csrfInput = document.createElement('input');
            csrfInput.name = '_csrf'; csrfInput.value = csrf;
            form.appendChild(csrfInput);

            const teamInput = document.createElement('input');
            teamInput.name = 'team_id'; teamInput.value = teamId;
            form.appendChild(teamInput);

            data.forEach(function(d, i) {
                ['member_id','position','jersey_number'].forEach(function(k) {
                    const inp = document.createElement('input');
                    inp.name = 'lineup[' + i + '][' + k + ']';
                    inp.value = d[k] || '';
                    form.appendChild(inp);
                });
            });

            document.body.appendChild(form);
            form.submit();
        }

        draw();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
