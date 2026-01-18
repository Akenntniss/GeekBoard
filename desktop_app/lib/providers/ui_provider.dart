import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class UiProvider extends ChangeNotifier {
  bool _isSidebarCollapsed = false;
  ThemeMode _themeMode = ThemeMode.system;

  bool get isSidebarCollapsed => _isSidebarCollapsed;
  ThemeMode get themeMode => _themeMode;

  UiProvider() {
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    _isSidebarCollapsed = prefs.getBool('sidebar_collapsed') ?? false;
    
    final themeStr = prefs.getString('theme_mode');
    if (themeStr == 'light') _themeMode = ThemeMode.light;
    else if (themeStr == 'dark') _themeMode = ThemeMode.dark;
    else _themeMode = ThemeMode.system;

    notifyListeners();
  }

  Future<void> toggleSidebar() async {
    _isSidebarCollapsed = !_isSidebarCollapsed;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('sidebar_collapsed', _isSidebarCollapsed);
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    _themeMode = mode;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    String val = 'system';
    if (mode == ThemeMode.light) val = 'light';
    if (mode == ThemeMode.dark) val = 'dark';
    await prefs.setString('theme_mode', val);
  }
}
