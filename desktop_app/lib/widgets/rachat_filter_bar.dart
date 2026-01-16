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
    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
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
                    color: const Color(0xFF0F172A),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.white.withOpacity(0.1)),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onChanged: (val) => _notifyChanged(),
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'Rechercher un rachat (client, modèle, IMEI)...',
                      hintStyle: TextStyle(color: Colors.grey[500]),
                      prefixIcon: Icon(Icons.search, color: Colors.grey[400]),
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
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white.withOpacity(0.1)),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: _selectedState,
                    dropdownColor: const Color(0xFF1E293B),
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    icon: const Icon(Icons.arrow_drop_down, color: Colors.grey),
                    onChanged: (val) {
                      setState(() {
                        _selectedState = val!;
                        _notifyChanged();
                      });
                    },
                    items: const [
                       DropdownMenuItem(value: 'all', child: Text('Tous les appareils')),
                       DropdownMenuItem(value: 'functional', child: Text('Fonctionnels')),
                       DropdownMenuItem(value: 'non-functional', child: Text('Non fonctionnels')),
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
