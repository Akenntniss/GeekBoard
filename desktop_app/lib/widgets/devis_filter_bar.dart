import 'package:flutter/material.dart';
import '../theme/macos_theme.dart';

class DevisFilterBar extends StatelessWidget {
  final String selectedFilter;
  final Function(String) onFilterSelected;
  final Map<String, int> counts;
  final Function(int devisId, String newStatus)? onStatusDropped;

  const DevisFilterBar({
    Key? key,
    required this.selectedFilter,
    required this.onFilterSelected,
    required this.counts,
    this.onStatusDropped,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Center(
        child: Wrap(
          spacing: 16,
          runSpacing: 16,
          alignment: WrapAlignment.center,
          children: [
            _buildFilterBtn(context, "En attente", "envoye", Icons.hourglass_empty, MacOSTheme.warningOrange),
            _buildFilterBtn(context, "Accepté", "accepte", Icons.check_circle, MacOSTheme.successGreen),
            _buildFilterBtn(context, "Refusé", "refuse", Icons.cancel, MacOSTheme.dangerRed),
            _buildFilterBtn(context, "Tout", "all", Icons.list_alt, MacOSTheme.accentBlue),
            _buildFilterBtn(context, "Expiré", "expire", Icons.timer_off, MacOSTheme.textSecondary),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterBtn(BuildContext context, String label, String code, IconData icon, Color accentColor) {
    final bool isSelected = selectedFilter == code;
    final int count = counts[code] ?? 0;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return DragTarget<int>(
      onWillAccept: (data) => data != null && code != 'all', // Can drop on specific statuses
      onAccept: (devisId) {
        if (onStatusDropped != null) {
          onStatusDropped!(devisId, code);
        }
      },
      builder: (context, candidateData, rejectedData) {
        final isHovered = candidateData.isNotEmpty;
        
        return InkWell(
          onTap: () => onFilterSelected(code),
          borderRadius: BorderRadius.circular(16),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            width: 140, // Consistent width with ReparationsFilterBar
            height: 100,
            decoration: BoxDecoration(
              color: isHovered 
                  ? accentColor.withOpacity(0.2) // Highlight on drag hover
                  : isSelected ? accentColor : Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                color: isHovered 
                    ? accentColor 
                    : isSelected ? accentColor : Theme.of(context).dividerColor,
                width: isHovered ? 2 : (isSelected ? 0 : 1),
              ),
              boxShadow: [
                if (isSelected || isHovered)
                  BoxShadow(
                    color: accentColor.withOpacity(0.4),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  )
                else
                  BoxShadow(
                    color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
              ],
            ),
            child: Stack(
              children: [
                 // Badge Count top right
                Positioned(
                  top: 8,
                  right: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: isSelected ? Colors.white.withOpacity(0.2) : accentColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      count.toString(),
                      style: TextStyle(
                        color: isSelected ? Colors.white : accentColor,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ),
                 // Icon and Label
                Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: isSelected ? Colors.white : accentColor, size: 32),
                      const SizedBox(height: 8),
                      Text(
                        label,
                        style: TextStyle(
                          color: isSelected ? Colors.white : Theme.of(context).textTheme.bodyLarge?.color,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
