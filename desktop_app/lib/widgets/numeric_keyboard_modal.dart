import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'dart:ui';
import '../theme/macos_theme.dart';

class NumericKeyboardModal extends StatefulWidget {
  final double initialValue;

  const NumericKeyboardModal({
    Key? key, 
    required this.initialValue
  }) : super(key: key);

  @override
  State<NumericKeyboardModal> createState() => _NumericKeyboardModalState();
}

class _NumericKeyboardModalState extends State<NumericKeyboardModal> {
  String _currentValue = '';

  @override
  void initState() {
    super.initState();
    if (widget.initialValue > 0) {
      _currentValue = widget.initialValue.toStringAsFixed(2);
      // Supprimer .00 inutile si entier
      if (_currentValue.endsWith('.00')) {
        _currentValue = _currentValue.substring(0, _currentValue.length - 3);
      }
    }
  }

  void _handleKeyPress(String value) {
    setState(() {
      if (value == 'backspace') {
        if (_currentValue.isNotEmpty) {
          _currentValue = _currentValue.substring(0, _currentValue.length - 1);
        }
      } else if (value == '.') {
        if (!_currentValue.contains('.')) {
           _currentValue += value;
        }
      } else {
        // Limiter la longueur et les décimales
        if (_currentValue.contains('.')) {
           int decimals = _currentValue.split('.')[1].length;
           if (decimals >= 2) return;
        }
        if (_currentValue == '0' && value != '.') {
          _currentValue = value;
        } else {
          _currentValue += value;
        }
      }
    });
  }

  void _submit() {
    double? val = double.tryParse(_currentValue);
    Navigator.pop(context, val);
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Center(
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
          child: Container(
            width: 320,
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: isDark ? Colors.black.withOpacity(0.7) : Colors.white.withOpacity(0.8),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: Colors.white.withOpacity(0.2),
                width: 1,
              ),
              boxShadow: [
                 BoxShadow(
                  color: Colors.black.withOpacity(0.3),
                  blurRadius: 30,
                  spreadRadius: 5,
                )
              ]
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Display
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
                  margin: const EdgeInsets.only(bottom: 20),
                  decoration: BoxDecoration(
                    color: Colors.transparent,
                  ),
                  child: Text(
                    _currentValue.isEmpty ? '0 €' : '$_currentValue €',
                    textAlign: TextAlign.right,
                    style: const TextStyle(
                      fontSize: 48,
                      fontWeight: FontWeight.w300,
                      color: Colors.white, // Toujours blanc pour le contraste sur fond sombre/blur
                      letterSpacing: -1,
                    ),
                  ),
                ),

                // Keypad
                GridView.count(
                  crossAxisCount: 3,
                  shrinkWrap: true,
                  mainAxisSpacing: 12,
                  crossAxisSpacing: 12,
                  childAspectRatio: 1.3,
                  children: [
                    _buildKey('1'), _buildKey('2'), _buildKey('3'),
                    _buildKey('4'), _buildKey('5'), _buildKey('6'),
                    _buildKey('7'), _buildKey('8'), _buildKey('9'),
                    _buildKey('.'), _buildKey('0'), _buildKey('backspace', icon: CupertinoIcons.delete_left),
                  ],
                ),

                const SizedBox(height: 24),
                
                // Validate Button
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: MacOSTheme.successGreen,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    ),
                    child: const Text("Valider", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(height: 12),
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text("Annuler", style: TextStyle(color: Colors.grey)),
                )
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildKey(String value, {IconData? icon}) {
    // Style iOS Keys
    return GestureDetector(
      onTap: () => _handleKeyPress(value),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.15),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(
          child: icon != null 
            ? Icon(icon, color: Colors.white, size: 24)
            : Text(
                value,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 28,
                  fontWeight: FontWeight.w400,
                ),
              ),
        ),
      ),
    );
  }
}
