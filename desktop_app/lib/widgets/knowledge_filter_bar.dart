import 'package:flutter/material.dart';

class KnowledgeFilterBar extends StatefulWidget {
  final Function(String search, int? categoryId) onFilterChanged;
  final List<Map<String, dynamic>> categories;

  const KnowledgeFilterBar({
    super.key,
    required this.onFilterChanged,
    required this.categories,
  });

  @override
  State<KnowledgeFilterBar> createState() => _KnowledgeFilterBarState();
}

class _KnowledgeFilterBarState extends State<KnowledgeFilterBar> {
  final TextEditingController _searchController = TextEditingController();
  int? _selectedCategory;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _notifyChanged() {
    widget.onFilterChanged(
      _searchController.text,
      _selectedCategory,
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
                      hintText: 'Rechercher un article, une procédure...',
                      hintStyle: TextStyle(color: Colors.grey[500]),
                      prefixIcon: Icon(Icons.search, color: Colors.grey[400]),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              
              // Category Dropdown
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                decoration: BoxDecoration(
                  color: const Color(0xFF0F172A),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white.withOpacity(0.1)),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<int>(
                    value: _selectedCategory,
                    hint: Text('Toutes catégories', style: TextStyle(color: Colors.grey[400], fontSize: 13)),
                    dropdownColor: const Color(0xFF1E293B),
                    style: const TextStyle(color: Colors.white, fontSize: 13),
                    icon: const Icon(Icons.arrow_drop_down, color: Colors.grey),
                    onChanged: (val) {
                      setState(() {
                        _selectedCategory = val;
                        _notifyChanged();
                      });
                    },
                    items: [
                       DropdownMenuItem<int>(
                        value: null, 
                        child: Text('Toutes catégories', style: TextStyle(color: Colors.grey[400])),
                      ),
                      ...widget.categories.map((c) => DropdownMenuItem<int>(
                        value: int.tryParse(c['id'].toString()),
                        child: Text(c['name']),
                      )).toList(),
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
