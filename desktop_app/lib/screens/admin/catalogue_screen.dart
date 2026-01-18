import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:provider/provider.dart';
import 'dart:async';
import '../../widgets/catalogue_product_card.dart';

class CatalogueScreen extends StatefulWidget {
  const CatalogueScreen({super.key});

  @override
  State<CatalogueScreen> createState() => _CatalogueScreenState();
}

class _CatalogueScreenState extends State<CatalogueScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  final ScrollController _scrollController = ScrollController();
  Timer? _debounce;
  final TextEditingController _searchController = TextEditingController();

  // Data
  List<dynamic> _products = [];
  Map<String, dynamic> _filtersData = {};
  
  // State
  bool _isLoading = true;
  int _currentPage = 1;
  int _totalPages = 1;

  // Active Filters
  String _selectedFournisseur = '';
  String _selectedBrand = '';
  String _selectedType = '';
  String _selectedDeviceType = '';
  bool _onlyStock = false;

  @override
  void initState() {
    super.initState();
    _loadFilters();
    _loadProducts();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent * 0.9 &&
        !_isLoading &&
        _currentPage < _totalPages) {
      _loadProducts(loadMore: true);
    }
  }

  void _onSearchChanged(String query) {
    if (_debounce?.isActive ?? false) _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 500), () {
      _currentPage = 1;
      _loadProducts();
    });
  }

  Future<void> _loadFilters() async {
    try {
      final response = await _apiService.get(ApiConfig.catalogueFiltersEndpoint);
      if (mounted) {
        setState(() {
          _filtersData = response ?? {};
        });
      }
    } catch (_) {}
  }

  Future<void> _loadProducts({bool loadMore = false}) async {
    if (loadMore) {
      _currentPage++;
    } else {
      _currentPage = 1;
      setState(() => _isLoading = true);
    }

    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '50',
        'search': _searchController.text,
        'stock': _onlyStock ? 'en_stock' : '',
        'fournisseur_id': _selectedFournisseur,
        'brand': _selectedBrand,
        'type': _selectedType,
        'device_type': _selectedDeviceType,
      };

      final response = await _apiService.get(ApiConfig.catalogueListEndpoint, queryParams: queryParams);
      
      if (mounted) {
        setState(() {
          final newProducts = response['products'] as List? ?? [];
          if (loadMore) {
            _products.addAll(newProducts);
          } else {
            _products = newProducts;
            _isLoading = false;
          }
          final meta = response['meta'] ?? {};
          _totalPages = meta['total_pages'] ?? 1;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur: $e')));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppShell(
      currentRoute: '/catalogue',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        appBar: AppBar(
          backgroundColor: const Color(0xFF1E293B),
          elevation: 0,
          title: const Text("Catalogue Fournisseurs", style: TextStyle(fontWeight: FontWeight.bold)),
          actions: [
             IconButton(
               icon: const Icon(Icons.refresh),
               onPressed: () => _loadProducts(),
             ),
          ],
        ),
        body: Column(
          children: [
            // Hero Filters Section
            Container(
              padding: const EdgeInsets.all(24),
              color: const Color(0xFF1E293B),
              child: Column(
                children: [
                  // Search Bar
                  TextField(
                    controller: _searchController,
                    onChanged: _onSearchChanged,
                    style: const TextStyle(color: Colors.white, fontSize: 16),
                    decoration: InputDecoration(
                      hintText: 'Rechercher un produit, référence, marque...',
                      hintStyle: TextStyle(color: Colors.grey[500]),
                      prefixIcon: const Icon(Icons.search, color: Colors.blueAccent),
                      filled: true,
                      fillColor: const Color(0xFF0F172A),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // Wrap Filters
                  Wrap(
                    spacing: 12,
                    runSpacing: 12,
                    children: [
                      // Stock Toggle
                      InkWell(
                        onTap: () {
                          setState(() => _onlyStock = !_onlyStock);
                          _loadProducts();
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          decoration: BoxDecoration(
                            color: _onlyStock ? Colors.green.withOpacity(0.2) : const Color(0xFF0F172A),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: _onlyStock ? Colors.green : Colors.transparent,
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.inventory, size: 16, color: _onlyStock ? Colors.green : Colors.grey),
                              const SizedBox(width: 8),
                              Text("En Stock", style: TextStyle(color: _onlyStock ? Colors.green : Colors.grey)),
                            ],
                          ),
                        ),
                      ),

                      // Dropdowns
                      _buildDropdown("Fournisseurs", _filtersData['fournisseurs'], _selectedFournisseur, (val) {
                        setState(() => _selectedFournisseur = val ?? '');
                        _loadProducts();
                      }, idKey: 'id', labelKey: 'nom'),

                      _buildDropdown("Marques", _filtersData['brands'], _selectedBrand, (val) {
                        setState(() => _selectedBrand = val ?? '');
                        _loadProducts();
                      }),

                      _buildDropdown("Types", _filtersData['types'], _selectedType, (val) {
                        setState(() => _selectedType = val ?? '');
                        _loadProducts();
                      }),
                      
                       _buildDropdown("Appareils", _filtersData['device_types'], _selectedDeviceType, (val) {
                        setState(() => _selectedDeviceType = val ?? '');
                        _loadProducts();
                      }),
                    ],
                  ),
                ],
              ),
            ),
            
            // Grid Content
            Expanded(
              child: _products.isEmpty && !_isLoading
                  ? const Center(child: Text('Aucun produit trouvé', style: TextStyle(color: Colors.grey)))
                  : GridView.builder(
                      controller: _scrollController,
                      padding: const EdgeInsets.all(24),
                      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                        maxCrossAxisExtent: 300,
                        childAspectRatio: 0.75,
                        crossAxisSpacing: 16,
                        mainAxisSpacing: 16,
                      ),
                      itemCount: _products.length + (_isLoading ? 1 : 0),
                      itemBuilder: (context, index) {
                         if (index == _products.length) {
                           return const Center(child: CircularProgressIndicator());
                         }
                         
                         return CatalogueProductCard(
                           product: _products[index],
                           onAddToCart: () {
                             // Mock add to cart for now
                             ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Ajout panier (Demo)")));
                           },
                         );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDropdown(
    String hint, 
    List<dynamic>? items, 
    String currentValue, 
    Function(String?) onChanged,
    {String idKey = '', String labelKey = ''}
  ) {
    if (items == null) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(8),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: currentValue.isEmpty ? null : currentValue,
          hint: Text(hint, style: const TextStyle(color: Colors.grey, fontSize: 13)),
          dropdownColor: const Color(0xFF0F172A),
          style: const TextStyle(color: Colors.white, fontSize: 13),
          icon: const Icon(Icons.arrow_drop_down, color: Colors.grey),
          onChanged: onChanged,
          items: [
             DropdownMenuItem(value: '', child: Text("Tous : $hint")),
             ...items.map((item) {
               final val = idKey.isNotEmpty ? item[idKey].toString() : item.toString();
               final label = labelKey.isNotEmpty ? item[labelKey].toString() : item.toString();
               return DropdownMenuItem(value: val, child: Text(label));
             }),
          ],
        ),
      ),
    );
  }
}
