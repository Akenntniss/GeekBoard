/// macOS-style theme for GeekBoard Desktop
import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import 'package:google_fonts/google_fonts.dart';

class MacOSTheme {
  // Colors inspired by macOS Taohe (Modern, Clean, Soft)
  static const Color sidebarBackground = Color(0xFFF5F5F7); // Light gray
  static const Color background = Color(0xFFF5F5F7); // Main app background
  static const Color sidebarBackgroundDark = Color(0xFF1E293B); // Deep Slate (matches cards)
  static const Color accentBlue = Color(0xFF2563EB); // Vibrant Dashboard Blue
  static const Color accentPurple = Color(0xFF5E5CE6); // macOS Purple
  static const Color successGreen = Color(0xFF34C759);
  static const Color warningOrange = Color(0xFFFF9500);
  static const Color dangerRed = Color(0xFFFF3B30);
  
  static const Color textPrimary = Color(0xFF1D1D1F);
  static const Color textSecondary = Color(0xFF86868B);
  
  static const Color cardBackground = Colors.white;
  static const Color divider = Color(0xFFE5E5E7); // Subtle divider

  // Grays
  static const Color gray900 = Color(0xFF1C1C1E);
  static const Color gray800 = Color(0xFF2C2C2E);
  static const Color gray400 = Color(0xFFAEAEB2);

  // Status colors
  static Color getStatusColor(String status) {
    status = status.toLowerCase();
    
    // RED (Cancelled/Archived/Critical)
    if (status.contains('annul') || 
        status.contains('abandon') || 
        status.contains('restitue') ||
        status.contains('non_reparable') ||
        status.contains('refus') ||
        status.contains('urgent')) {
      return dangerRed; 
    }
    
    // GREEN (Done/Success)
    if (status.contains('effectue') || 
        status.contains('termine') || 
        status.contains('livre') ||
        status.contains('accepte')) {
      return successGreen;
    }
    
    // ORANGE (Processing/Warn)
    if (status.contains('cours') || 
        status.contains('diagnostique') || 
        status.contains('attente')) {
      return warningOrange;
    }
    
    // BLUE (New/Info)
    if (status.contains('nouvelle') || 
        status.contains('nouveau') ||
        status.contains('commande')) {
      return accentBlue;
    }
    
    // Default
    return textSecondary;
  }

  // Priority colors
  static Color getPriorityColor(String priority) {
    switch (priority.toLowerCase()) {
      case 'haute':
        return dangerRed;
      case 'moyenne':
        return warningOrange;
      case 'basse':
        return accentBlue;
      default:
        return textSecondary;
    }
  }

  // Light Theme
  static ThemeData get lightTheme => ThemeData(
    useMaterial3: true,
    brightness: Brightness.light,
    scaffoldBackgroundColor: const Color(0xFFF5F5F7), // Main bg
    colorScheme: ColorScheme.fromSeed(
      seedColor: accentBlue,
      brightness: Brightness.light,
      surface: Colors.white,
      background: const Color(0xFFF5F5F7),
    ),
    // Typography: Inter (clean, modern)
    textTheme: GoogleFonts.interTextTheme().apply(
      bodyColor: textPrimary, 
      displayColor: textPrimary
    ),
    
    // Cards
    cardTheme: CardThemeData(
      elevation: 0,
      color: cardBackground,
      margin: const EdgeInsets.symmetric(vertical: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16), // Softer corners
        side: const BorderSide(color: Color(0xFFE5E5E7), width: 0.8),
      ),
    ),

    // Inputs
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10), // Apple-like
        borderSide: const BorderSide(color: Color(0xFFD1D1D6), width: 1),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFD1D1D6), width: 1),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: accentBlue, width: 2),
      ),
      hintStyle: const TextStyle(color: Color(0xFFAEAEB2)),
    ),

    // Buttons
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: accentBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
        textStyle: GoogleFonts.inter(fontWeight: FontWeight.w600),
      ),
    ),
    
    // Dialogs
    dialogTheme: DialogThemeData(
      backgroundColor: Colors.white.withOpacity(0.95), // Slight translucency effect
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      elevation: 20,
      shadowColor: Colors.black.withOpacity(0.2),
    ),

    dividerTheme: const DividerThemeData(
      color: divider,
      thickness: 1,
    ),
    
    iconTheme: const IconThemeData(color: textPrimary, size: 20),
  );

  // Gradient
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF007AFF), Color(0xFF5AC8FA)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  // Dark Theme
  static ThemeData get darkTheme => ThemeData(
    useMaterial3: true,
    brightness: Brightness.dark,
    scaffoldBackgroundColor: const Color(0xFF0F172A), // Deep Navy (KPI Dashboard style)
    colorScheme: ColorScheme.fromSeed(
      seedColor: accentBlue,
      brightness: Brightness.dark,
      surface: const Color(0xFF1E293B), // Slate Dark Card
      background: const Color(0xFF0F172A),
    ),
    
    textTheme: GoogleFonts.interTextTheme(ThemeData.dark().textTheme).apply(
      bodyColor: const Color(0xFFF5F5F7), // Soft White
      displayColor: Colors.white,
    ),

    cardTheme: CardThemeData(
      elevation: 0,
      color: const Color(0xFF1E293B), // Slate Dark Card (KPI style)
      margin: const EdgeInsets.symmetric(vertical: 8),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.white.withOpacity(0.08), width: 0.5), // Very subtle border
      ),
    ),

    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: const Color(0xFF334155), // Slate-700 for inputs
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: accentBlue, width: 2),
      ),
      hintStyle: TextStyle(color: Colors.white.withOpacity(0.3)),
    ),
    
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: accentBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
        textStyle: GoogleFonts.inter(fontWeight: FontWeight.w600),
      ),
    ),

    dialogTheme: DialogThemeData(
      backgroundColor: const Color(0xFF1E293B).withOpacity(0.98),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18), side: BorderSide(color: Colors.white.withOpacity(0.1), width: 0.5)),
      elevation: 40,
      shadowColor: Colors.black.withOpacity(0.5),
    ),
  );
}
/// macOS-style card with subtle shadow
class MacOSCard extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry? padding;
  final VoidCallback? onTap;

  const MacOSCard({
    super.key,
    required this.child,
    this.padding,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: padding ?? const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E293B) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDark 
              ? Colors.white.withOpacity(0.08) 
              : const Color(0xFFE5E5E7),
            width: 0.5,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(isDark ? 0.3 : 0.05),
              blurRadius: 10,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: child,
      ),
    );
  }
}

/// macOS-style status badge
class StatusBadge extends StatelessWidget {
  final String status;
  final String? label;

  const StatusBadge({super.key, required this.status, this.label});

  @override
  Widget build(BuildContext context) {
    final color = MacOSTheme.getStatusColor(status);
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label ?? _formatStatus(status),
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  String _formatStatus(String status) {
    return status
        .replaceAll('_', ' ')
        .split(' ')
        .map((word) => word.isNotEmpty 
            ? word[0].toUpperCase() + word.substring(1) 
            : '')
        .join(' ');
  }
}

/// macOS-style priority badge
class PriorityBadge extends StatelessWidget {
  final String priority;

  const PriorityBadge({super.key, required this.priority});

  @override
  Widget build(BuildContext context) {
    final color = MacOSTheme.getPriorityColor(priority);
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.15),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        priority.toUpperCase(),
        style: TextStyle(
          color: color,
          fontSize: 10,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}
