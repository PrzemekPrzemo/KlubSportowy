import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/exceptions.dart';
import '../../localization/app_localizations.dart';
import 'empty_view.dart';
import 'error_view.dart';
import 'loading_view.dart';

/// Reusable widget renderujący AsyncValue z standardowymi loading/error stanami.
class AsyncValueView<T> extends StatelessWidget {
  const AsyncValueView({
    super.key,
    required this.value,
    required this.data,
    this.onRetry,
    this.empty,
    this.isEmpty,
  });

  final AsyncValue<T> value;
  final Widget Function(T data) data;
  final VoidCallback? onRetry;
  final Widget? empty;
  final bool Function(T data)? isEmpty;

  @override
  Widget build(BuildContext context) {
    return value.when(
      data: (d) {
        if (isEmpty?.call(d) ?? false) {
          return empty ?? EmptyView(message: context.tr('empty_default'));
        }
        return data(d);
      },
      loading: () => const LoadingView(),
      error: (err, _) {
        final message = err is AppException
            ? err.message
            : context.tr('error_generic');
        return ErrorView(message: message, onRetry: onRetry);
      },
    );
  }
}
