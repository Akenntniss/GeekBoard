import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class UiProvider extends ChangeNotifier {
  bool _isSidebarCollapsed = false;

  bool get isSidebarCollapsed => _isSidebarCollapsed;

  UiProvider() {
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    _isSidebarCollapsed = prefs.getBool('sidebar_collapsed') ?? false;
    notifyListeners();
  }

  Future<void> toggleSidebar() async {
    _isSidebarCollapsed = !_isSidebarCollapsed;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('sidebar_collapsed', _isSidebarCollapsed);
  }
}
