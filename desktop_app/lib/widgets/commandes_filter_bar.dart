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
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Center(
        child: Wrap(
          spacing: 16,
          runSpacing: 16,
          alignment: WrapAlignment.center,
          children: [
            _buildFilterBtn("Tous", "all", Icons.list_alt, Colors.blue),
            _buildFilterBtn("En attente", "en_attente", Icons.hourglass_empty, Colors.orange),
            _buildFilterBtn("Commandé", "commande", Icons.local_shipping, Colors.cyan),
            _buildFilterBtn("Reçu", "recue", Icons.check_circle, Colors.green),
            _buildFilterBtn("Utilisé", "utilise", Icons.build, Colors.indigo), // Using indigo for Primary to distinguish from "Tous" blue
            _buildFilterBtn("Retour", "a_retourner", Icons.undo, Colors.grey),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterBtn(String label, String code, IconData icon, Color accentColor) {
    final bool isSelected = selectedFilter == code;
    final int count = counts[code] ?? 0;

    return InkWell(
      onTap: () => onFilterSelected(code),
      borderRadius: BorderRadius.circular(50),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? accentColor.withOpacity(0.2) : const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(50),
          border: Border.all(
            color: isSelected ? accentColor : Colors.white.withOpacity(0.1),
            width: isSelected ? 2 : 1,
          ),
          boxShadow: [
            BoxShadow(
              color: isSelected ? accentColor.withOpacity(0.3) : Colors.black.withOpacity(0.1),
              blurRadius: 10,
              offset: const Offset(0, 4),
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
                color: isSelected ? Colors.white : Colors.grey[400],
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
