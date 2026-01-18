import 'package:flutter/material.dart';

class CatalogueFilterBar extends StatefulWidget {
  final Function(String search, bool stockOnly, String? type, String? brand, String? provider) onFilterChanged;
  final List<String> types;
  final List<String> brands;
  final List<Map<String, dynamic>> providers;

  const CatalogueFilterBar({
    super.key,
    required this.onFilterChanged,
    required this.types,
    required this.brands,
    required this.providers,
  });

  @override
  State<CatalogueFilterBar> createState() => _CatalogueFilterBarState();
}

class _CatalogueFilterBarState extends State<CatalogueFilterBar> {
  final TextEditingController _searchController = TextEditingController();
  bool _stockOnly = false;
  String? _selectedType;
  String? _selectedBrand;
  String? _selectedProvider;

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _notifyChanged() {
    widget.onFilterChanged(
      _searchController.text,
      _stockOnly,
      _selectedType,
      _selectedBrand,
      _selectedProvider,
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          // Search Hero
          Row(
            children: [
              Expanded(
                child: Container(
                  height: 50,
                  decoration: BoxDecoration(
                    color: isDark ? const Color(0xFF0F172A) : Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : Colors.transparent),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onChanged: (val) => _notifyChanged(),
                    style: TextStyle(color: isDark ? Colors.white : Colors.black87),
                    decoration: InputDecoration(
                      hintText: 'Rechercher un produit, une référence, une marque...',
                      hintStyle: TextStyle(color: isDark ? Colors.grey[500] : Colors.grey[600]),
                      prefixIcon: Icon(Icons.search, color: isDark ? Colors.grey[400] : Colors.grey),
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          // Filters Row
          Row(
            children: [
              // Stock Switch
              InkWell(
                onTap: () {
                  setState(() {
                    _stockOnly = !_stockOnly;
                    _notifyChanged();
                  });
                },
                borderRadius: BorderRadius.circular(30),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(
                    color: _stockOnly ? const Color(0xFF10B981).withOpacity(0.2) : Colors.transparent,
                    borderRadius: BorderRadius.circular(30),
                    border: Border.all(
                      color: _stockOnly ? const Color(0xFF10B981) : (isDark ? Colors.white.withOpacity(0.2) : Colors.grey.shade300),
                    ),
                  ),
                  child: Row(
                    children: [
                      Switch(
                        value: _stockOnly,
                        onChanged: (val) {
                          setState(() {
                            _stockOnly = val;
                            _notifyChanged();
                          });
                        },
                        activeColor: const Color(0xFF10B981),
                        activeTrackColor: const Color(0xFF10B981).withOpacity(0.4),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        'En stock uniquement',
                        style: TextStyle(
                          color: _stockOnly ? (isDark ? Colors.white : Colors.black87) : (isDark ? Colors.grey[400] : Colors.grey[600]),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 24),
              Container(width: 1, height: 30, color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade300),
              const SizedBox(width: 24),
              
              // Provider Dropdown
              _buildDropdown<String>(
                value: _selectedProvider,
                hint: 'Tous les fournisseurs',
                items: widget.providers.map((p) => DropdownMenuItem(
                  value: p['id'].toString(),
                  child: Text(p['nom']),
                )).toList(),
                onChanged: (val) {
                  setState(() => _selectedProvider = val);
                  _notifyChanged();
                },
              ),
              const SizedBox(width: 12),
              
              // Type Dropdown
              _buildDropdown<String>(
                value: _selectedType,
                hint: 'Tous les types',
                items: widget.types.map((t) => DropdownMenuItem(
                  value: t,
                  child: Text(t),
                )).toList(),
                onChanged: (val) {
                  setState(() => _selectedType = val);
                  _notifyChanged();
                },
              ),
              const SizedBox(width: 12),
              
              // Brand Dropdown
              _buildDropdown<String>(
                value: _selectedBrand,
                hint: 'Toutes les marques',
                items: widget.brands.map((b) => DropdownMenuItem(
                  value: b,
                  child: Text(b),
                )).toList(),
                onChanged: (val) {
                  setState(() => _selectedBrand = val);
                  _notifyChanged();
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown<T>({
    required T? value,
    required String hint,
    required List<DropdownMenuItem<T>> items,
    required Function(T?) onChanged,
  }) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A) : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : Colors.transparent),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<T>(
          value: value,
          hint: Text(hint, style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600], fontSize: 13)),
          dropdownColor: isDark ? const Color(0xFF1E293B) : Colors.white,
          style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontSize: 13),
          icon: const Icon(Icons.arrow_drop_down, color: Colors.grey),
          onChanged: onChanged,
          items: [
            DropdownMenuItem<T>(
              value: null,
              child: Text(hint, style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600])),
            ),
            ...items,
          ],
        ),
      ),
    );
  }
}
