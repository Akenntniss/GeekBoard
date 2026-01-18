import 'package:flutter/material.dart';

class CommandesFilterBar extends StatelessWidget {
  final String selectedFilter;
  final Function(String) onFilterSelected;
  final Map<String, int> counts;

  const CommandesFilterBar({
    Key? key,
    required this.selectedFilter,
    required this.onFilterSelected,
    required this.counts,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Center(
        child: Wrap(
          spacing: 16,
          runSpacing: 16,
          alignment: WrapAlignment.center,
          children: [
            _buildFilterBtn("Tous", "all", Icons.list_alt, Colors.blue, isDark),
            _buildFilterBtn("En attente", "en_attente", Icons.hourglass_empty, Colors.orange, isDark),
            _buildFilterBtn("Commandé", "commande", Icons.local_shipping, Colors.cyan, isDark),
            _buildFilterBtn("Reçu", "recue", Icons.check_circle, Colors.green, isDark),
            _buildFilterBtn("Utilisé", "utilise", Icons.build, Colors.indigo, isDark),
            _buildFilterBtn("Retour", "a_retourner", Icons.undo, Colors.grey, isDark),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterBtn(String label, String code, IconData icon, Color accentColor, bool isDark) {
    final bool isSelected = selectedFilter == code;
    final int count = counts[code] ?? 0;

    return InkWell(
      onTap: () => onFilterSelected(code),
      borderRadius: BorderRadius.circular(50),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? accentColor.withOpacity(0.15) : (isDark ? const Color(0xFF1E293B) : Colors.white),
          borderRadius: BorderRadius.circular(50),
          border: Border.all(
            color: isSelected ? accentColor.withOpacity(0.5) : (isDark ? Colors.white.withOpacity(0.1) : const Color(0xFFE2E8F0)),
            width: 1,
          ),
          boxShadow: [
            BoxShadow(
              color: isSelected ? accentColor.withOpacity(0.1) : Colors.black.withOpacity(0.02),
              blurRadius: 4,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: accentColor, size: 20),
            const SizedBox(width: 12),
            Text(
              label,
              style: TextStyle(
                color: isSelected ? (isDark ? Colors.white : Colors.black87) : (isDark ? Colors.grey[400] : Colors.grey[700]),
                fontWeight: FontWeight.bold,
                fontSize: 14
              ),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: accentColor.withOpacity(0.2),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                count.toString(),
                style: TextStyle(
                  color: accentColor,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
