import 'package:flutter/material.dart';

class RachatFilterBar extends StatefulWidget {
  final Function(String search, bool showFunctional, bool showNonFunctional) onFilterChanged;

  const RachatFilterBar({
    super.key,
    required this.onFilterChanged,
  });

  @override
  State<RachatFilterBar> createState() => _RachatFilterBarState();
}

class _RachatFilterBarState extends State<RachatFilterBar> {
  final TextEditingController _searchController = TextEditingController();
  bool _showFunctional = false; // Note: Original UI uses a select: All, Functional, Non-functional. We can stick to search primarily as per plan, but let's add the dropdown visually to match.
  String _selectedState = 'all'; // all, functional, non-functional

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _notifyChanged() {
    widget.onFilterChanged(
      _searchController.text,
      _selectedState == 'functional',
      _selectedState == 'non-functional',
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final inputColor = isDark ? const Color(0xFF0F172A) : Colors.grey[100];
    final borderColor = isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade300;
    final textColor = isDark ? Colors.white : Colors.black87;
    final hintColor = isDark ? Colors.grey[500] : Colors.grey[600];
    final shadowColor = isDark ? Colors.black.withOpacity(0.2) : Colors.black.withOpacity(0.05);

    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: shadowColor,
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              // Search Input
              Expanded(
                child: Container(
                  height: 50,
                  decoration: BoxDecoration(
                    color: inputColor,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: borderColor),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onChanged: (val) => _notifyChanged(),
                    style: TextStyle(color: textColor),
                    decoration: InputDecoration(
                      hintText: 'Rechercher un rachat (client, modèle, IMEI)...',
                      hintStyle: TextStyle(color: hintColor),
                      prefixIcon: Icon(Icons.search, color: hintColor),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              
              // State Dropdown
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: inputColor,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: borderColor),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: _selectedState,
                    dropdownColor: cardColor,
                    style: TextStyle(color: textColor, fontSize: 13),
                    icon: Icon(Icons.arrow_drop_down, color: hintColor),
                    onChanged: (val) {
                      setState(() {
                        _selectedState = val!;
                        _notifyChanged();
                      });
                    },
                    items: [
                       DropdownMenuItem(value: 'all', child: Text('Tous les appareils', style: TextStyle(color: textColor))),
                       DropdownMenuItem(value: 'functional', child: Text('Fonctionnels', style: TextStyle(color: textColor))),
                       DropdownMenuItem(value: 'non-functional', child: Text('Non fonctionnels', style: TextStyle(color: textColor))),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
