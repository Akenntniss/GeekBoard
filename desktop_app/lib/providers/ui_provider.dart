import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class UiProvider extends ChangeNotifier {
  bool _isSidebarCollapsed = false;
  ThemeMode _themeMode = ThemeMode.system;
  double _scaleFactor = 1.0;

  // Constantes de zoom
  static const double _minScale = 0.5;
  static const double _maxScale = 2.0;
  static const double _scaleStep = 0.05;

  bool get isSidebarCollapsed => _isSidebarCollapsed;
  ThemeMode get themeMode => _themeMode;
  double get scaleFactor => _scaleFactor;
  int get scalePercent => (_scaleFactor * 100).round();

  UiProvider() {
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    final prefs = await SharedPreferences.getInstance();
    _isSidebarCollapsed = prefs.getBool('sidebar_collapsed') ?? false;
    _scaleFactor = prefs.getDouble('ui_scale_factor') ?? 1.0;
    
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

  /// Zoom avant (CMD+ / CTRL+)
  Future<void> zoomIn() async {
    if (_scaleFactor < _maxScale) {
      _scaleFactor = (_scaleFactor + _scaleStep).clamp(_minScale, _maxScale);
      notifyListeners();
      await _saveScaleFactor();
    }
  }

  /// Zoom arrière (CMD- / CTRL-)
  Future<void> zoomOut() async {
    if (_scaleFactor > _minScale) {
      _scaleFactor = (_scaleFactor - _scaleStep).clamp(_minScale, _maxScale);
      notifyListeners();
      await _saveScaleFactor();
    }
  }

  /// Reset zoom à 100% (CMD+0 / CTRL+0)
  Future<void> resetZoom() async {
    _scaleFactor = 1.0;
    notifyListeners();
    await _saveScaleFactor();
  }

  Future<void> _saveScaleFactor() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setDouble('ui_scale_factor', _scaleFactor);
  }
}
