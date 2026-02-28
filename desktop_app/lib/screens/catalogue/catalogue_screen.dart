import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../widgets/catalogue_filter_bar.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../settings/settings_screen.dart';

class CatalogueScreen extends StatefulWidget {
  const CatalogueScreen({super.key});

  @override
  State<CatalogueScreen> createState() => _CatalogueScreenState();
}

class _CatalogueScreenState extends State<CatalogueScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  // Data
  List<Map<String, dynamic>> _products = [];
  Map<String, dynamic> _stats = {};
  
  // Meta filters (loaded from API)
  List<String> _types = [];
  List<String> _brands = [];
  List<Map<String, dynamic>> _providers = [];
  
  // Pagination
  int _currentPage = 1;
  int _totalPages = 1;
  bool _isLoading = true;
  
  // Filters State
  String _search = '';
  bool _stockOnly = false;
  String? _selectedType;
  String? _selectedBrand;
  String? _selectedProvider;

  @override
  void initState() {
    super.initState();
    _loadData(loadMeta: true);
  }

  Future<void> _loadData({bool loadMeta = false}) async {
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '50',
        'search': _search,
      };
      
      if (_stockOnly) queryParams['stock'] = 'en_stock';
      if (_selectedType != null) queryParams['type'] = _selectedType!;
      if (_selectedBrand != null) queryParams['brand'] = _selectedBrand!;
      if (_selectedProvider != null) queryParams['fournisseur_id'] = _selectedProvider!;
      if (loadMeta) queryParams['include_meta'] = 'true';

      final response = await _apiService.get(ApiConfig.catalogueSupplierListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          if (response['products'] != null) {
            _products = List<Map<String, dynamic>>.from(response['products']);
          } else {
             _products = [];
          }
          
          if (response['pagination'] != null) {
            _totalPages = response['pagination']['totalPages'] ?? 1;
          }
          
          if (loadMeta && response['meta'] != null) {
            final meta = response['meta'];
            if (meta['types'] != null) _types = List<String>.from(meta['types']);
            if (meta['brands'] != null) _brands = List<String>.from(meta['brands']);
            if (meta['fournisseurs'] != null) _providers = List<Map<String, dynamic>>.from(meta['fournisseurs']);
            if (meta['stats'] != null) _stats = meta['stats'];
          }
        });
      }
    } catch (e) {
      print('Error loading catalogue: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur chargement: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return AppShell(
      currentRoute: '/catalogue',
      content: Scaffold(
        backgroundColor: isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
        body: Column(
          children: [
            // Header Stats
            _buildStatsHeader(),
            
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  children: [
                    // Filters
                    CatalogueFilterBar(
                      onFilterChanged: (search, stockOnly, type, brand, provider) {
                        setState(() {
                          _search = search;
                          _stockOnly = stockOnly;
                          _selectedType = type;
                          _selectedBrand = brand;
                          _selectedProvider = provider;
                          _currentPage = 1;
                        });
                        _loadData();
                      },
                      types: _types,
                      brands: _brands,
                      providers: _providers,
                    ),
                    
                    // Table
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: isDark ? const Color(0xFF1E293B) : Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: isDark ? Border.all(color: Colors.white.withOpacity(0.1)) : Border.all(color: Colors.grey.shade200),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
                              blurRadius: 20,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        child: _isLoading 
                          ? const Center(child: CircularProgressIndicator()) 
                          : _products.isEmpty 
                              ? _buildEmptyState()
                              : Column(
                                  children: [
                                    _buildTableHeader(),
                                    Expanded(
                                      child: ListView.separated(
                                        padding: EdgeInsets.zero,
                                        itemCount: _products.length,
                                        separatorBuilder: (ctx, i) => Divider(
                                          height: 1, 
                                          thickness: 1, 
                                          color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade100
                                        ),
                                        itemBuilder: (context, index) {
                                          return _buildProductRow(_products[index]);
                                        },
                                      ),
                                    ),
                                    _buildPagination(),
                                  ],
                                ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatsHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
      child: Row(
        children: [
          Expanded(
            child: _buildStatCard(
              'Total Produits', 
              (_stats['total'] ?? 0).toString(), 
              Icons.inventory_2_outlined, 
              Colors.blue
            )
          ),
          const SizedBox(width: 16),
          Expanded(
            child: _buildStatCard(
              'En Stock', 
              (_stats['en_stock'] ?? 0).toString(), 
              Icons.check_circle_outline, 
              const Color(0xFF10B981)
            )
          ),
          const SizedBox(width: 16),
          Expanded(
            child: _buildStatCard(
              'Rupture', 
              (_stats['rupture'] ?? 0).toString(), 
              Icons.error_outline, 
              const Color(0xFFEF4444)
            )
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String label, String value, IconData icon, Color color) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade200),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 28),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                value,
                style: TextStyle(
                  color: isDark ? Colors.white : Colors.black87,
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  letterSpacing: -0.5,
                ),
              ),
              Text(
                label.toUpperCase(),
                style: TextStyle(
                  color: isDark ? Colors.grey[400] : Colors.grey[600],
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 1,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTableHeader() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A).withOpacity(0.5) : Colors.grey.shade50,
        border: Border(bottom: BorderSide(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200)),
      ),
      child: Row(
        children: [
          Expanded(flex: 3, child: _buildHeaderCell('PRODUIT')),
          Expanded(flex: 1, child: _buildHeaderCell('PRIX HT')),
          const SizedBox(width: 24), // Add spacing
          Expanded(flex: 1, child: _buildHeaderCell('RÉFÉRENCE')),
          Expanded(flex: 1, child: _buildHeaderCell('MARQUE')),
          Expanded(flex: 1, child: _buildHeaderCell('MODÈLE')),
          Expanded(flex: 1, child: _buildHeaderCell('STOCK')),
          const SizedBox(width: 50), // Action button space
        ],
      ),
    );
  }

  Widget _buildHeaderCell(String text) {
    return Text(
      text,
      style: TextStyle(
        color: Colors.grey[500],
        fontSize: 11,
        fontWeight: FontWeight.bold,
        letterSpacing: 1,
      ),
    );
  }

  Widget _buildProductRow(Map<String, dynamic> product) {
    final bool inStock = (product['stock'] ?? '').toString().contains('En stock');
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: Row(
        children: [
          // Product Name & Provider
          Expanded(
            flex: 3,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  product['name'] ?? 'Sans nom',
                  style: TextStyle(
                    color: isDark ? Colors.white : Colors.black87, 
                    fontWeight: FontWeight.w600,
                    fontSize: 14
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                if (product['fournisseur_nom'] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.blue.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(4),
                        border: Border.all(color: Colors.blue.withOpacity(0.3)),
                      ),
                      child: Text(
                        product['fournisseur_nom'],
                        style: const TextStyle(color: Colors.blue, fontSize: 10, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          
          // Price
          Expanded(
            flex: 1,
            child: (product['is_locked'] == true || product['price'] == null)
              ? MouseRegion(
                  cursor: SystemMouseCursors.click,
                  child: GestureDetector(
                    onTap: () {
                      Navigator.of(context).pushReplacement(
                        PageRouteBuilder(
                          pageBuilder: (_, __, ___) => const SettingsScreen(initialTab: 8),
                        ),
                      );
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.grey.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey.withOpacity(0.3)),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.lock, size: 12, color: Colors.grey[700]),
                          const SizedBox(width: 4),
                          Text(
                            "Se connecter",
                            style: TextStyle(
                              color: Colors.grey[700],
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          )
                        ],
                      ),
                    ),
                  ),
                )
              : Container(
               padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
               decoration: BoxDecoration(
                 color: const Color(0xFF10B981).withOpacity(0.1),
                 borderRadius: BorderRadius.circular(8),
                 border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
               ),
               child: Text(
                 '${(double.tryParse(product['price'].toString()) ?? 0).toStringAsFixed(2)} €',
                 style: const TextStyle(
                   color: Color(0xFF10B981),
                   fontWeight: FontWeight.bold,
                   fontFamily: 'monospace',
                 ),
                 textAlign: TextAlign.center,
               ),
            ),
          ),
          
          const SizedBox(width: 24), // Add spacing

          // Reference
          Expanded(
            flex: 1, 
            child: Text(
              product['reference'] ?? '-', 
              style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600], fontFamily: 'monospace', fontSize: 12),
            )
          ),
          
          // Brand
          Expanded(flex: 1, child: Text(product['brand'] ?? '-', style: TextStyle(color: isDark ? Colors.white70 : Colors.black87))),
          
          // Model
          Expanded(flex: 1, child: Text(product['model'] ?? '-', style: TextStyle(color: isDark ? Colors.white70 : Colors.black87))),
          
          // Stock
          Expanded(
            flex: 1,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: inStock ? const Color(0xFF10B981).withOpacity(0.1) : const Color(0xFFEF4444).withOpacity(0.1),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: inStock ? const Color(0xFF10B981).withOpacity(0.2) : const Color(0xFFEF4444).withOpacity(0.2),
                ),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    inStock ? Icons.check_circle_outline : Icons.cancel_outlined,
                    size: 14,
                    color: inStock ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    inStock ? 'En stock' : 'Rupture',
                    style: TextStyle(
                      color: inStock ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ],
              ),
            ),
          ),
          
          // Action (Cart)
          SizedBox(
            width: 50,
            child: IconButton(
              icon: const Icon(Icons.add_shopping_cart, color: Colors.blue),
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Fonctionnalité ajouter au panier à venir')),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.search_off, size: 64, color: isDark ? Colors.grey[700] : Colors.grey[300]),
          const SizedBox(height: 16),
          Text(
            'Aucun produit trouvé',
            style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600], fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'Essayez de modifier vos filtres',
            style: TextStyle(color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A).withOpacity(0.3) : Colors.grey.shade50,
        border: Border(top: BorderSide(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: Icon(Icons.chevron_left, color: isDark ? Colors.white : Colors.black87),
            onPressed: _currentPage > 1 ? () {
              setState(() => _currentPage--);
              _loadData();
            } : null,
          ),
          const SizedBox(width: 16),
          Text(
            'Page $_currentPage / $_totalPages',
            style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontWeight: FontWeight.bold),
          ),
          const SizedBox(width: 16),
          IconButton(
            icon: Icon(Icons.chevron_right, color: isDark ? Colors.white : Colors.black87),
            onPressed: _currentPage < _totalPages ? () {
              setState(() => _currentPage++);
              _loadData();
            } : null,
          ),
        ],
      ),
    );
  }
}
