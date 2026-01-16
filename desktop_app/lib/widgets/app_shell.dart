/// App Shell - Layout wrapper with persistent sidebar
import 'package:flutter/material.dart';
import 'sidebar.dart';

class AppShell extends StatelessWidget {
  final String currentRoute;
  final Widget content;
  
  const AppShell({
    super.key,
    required this.currentRoute,
    required this.content,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          Sidebar(currentRoute: currentRoute),
          Expanded(
            child: Container(
              color: Theme.of(context).scaffoldBackgroundColor,
              child: content,
            ),
          ),
        ],
      ),
    );
  }
}
