import 'package:flutter/material.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import '../../widgets/knowledge_filter_bar.dart';

class KnowledgeBaseScreen extends StatefulWidget {
  const KnowledgeBaseScreen({super.key});

  @override
  State<KnowledgeBaseScreen> createState() => _KnowledgeBaseScreenState();
}

class _KnowledgeBaseScreenState extends State<KnowledgeBaseScreen> {
  final ApiService _apiService = ApiService();
  
  // Data
  List<Map<String, dynamic>> _articles = [];
  List<Map<String, dynamic>> _categories = [];
  
  // Pagination
  int _currentPage = 1;
  int _totalPages = 1;
  bool _isLoading = true;
  
  // Filters State
  String _search = '';
  int? _selectedCategory;

  @override
  void initState() {
    super.initState();
    _loadData(loadCategories: true);
  }

  Future<void> _loadData({bool loadCategories = false}) async {
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '12', // Card view needs fewer items per page usually
        'search': _search,
      };
      
      if (_selectedCategory != null) {
        queryParams['category_id'] = _selectedCategory.toString();
      }
      
      if (loadCategories) {
        queryParams['include_categories'] = 'true';
      }

      final response = await _apiService.get(ApiConfig.knowledgeListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          if (response['articles'] != null) {
            _articles = List<Map<String, dynamic>>.from(response['articles']);
          } else {
             _articles = [];
          }
          
          if (loadCategories && response['categories'] != null) {
            _categories = List<Map<String, dynamic>>.from(response['categories']);
          }
          
          if (response['pagination'] != null) {
            _totalPages = response['pagination']['totalPages'] ?? 1;
          }
        });
      }
    } catch (e) {
      print('Error loading knowledge base: $e');
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
    return AppShell(
      currentRoute: '/knowledge',
      content: Scaffold(
        backgroundColor: const Color(0xFF0F172A),
        body: Column(
          children: [
            // Header
            _buildHeader(),
            
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  children: [
                    // Filters
                    KnowledgeFilterBar(
                      onFilterChanged: (search, categoryId) {
                        setState(() {
                          _search = search;
                          _selectedCategory = categoryId;
                          _currentPage = 1;
                        });
                        _loadData();
                      },
                      categories: _categories,
                    ),
                    
                    // Stats / Quick Info (Optional, skipping for now to focus on grid)
                    
                    // Grid View
                    Expanded(
                      child: _isLoading 
                        ? const Center(child: CircularProgressIndicator()) 
                        : _articles.isEmpty 
                            ? _buildEmptyState()
                            : Column(
                                children: [
                                  Expanded(
                                    child: GridView.builder(
                                      padding: EdgeInsets.zero,
                                      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
                                        maxCrossAxisExtent: 400,
                                        childAspectRatio: 1.3,
                                        crossAxisSpacing: 20,
                                        mainAxisSpacing: 20,
                                      ),
                                      itemCount: _articles.length,
                                      itemBuilder: (context, index) {
                                        return _buildArticleCard(_articles[index]);
                                      },
                                    ),
                                  ),
                                  const SizedBox(height: 20),
                                  _buildPagination(),
                                ],
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

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 24, 24, 0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF3b82f6), Color(0xFF06b6d4)], // Blue to Cyan
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.school, color: Colors.white, size: 24),
              ),
              const SizedBox(width: 16),
              const Text(
                'Base de Connaissances',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: Colors.white,
                  letterSpacing: -0.5,
                ),
              ),
            ],
          ),
          ElevatedButton.icon(
            onPressed: () {
               ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Fonctionnalité Créer Article à venir')),
              );
            },
            icon: const Icon(Icons.add, size: 18),
            label: const Text('Nouvel Article'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3b82f6),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              elevation: 4,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildArticleCard(Map<String, dynamic> article) {
    // Extract colors for category pill (pseudo-random or based on id)
    final catColor = Colors.blue; 
    
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
             // Open details...
             ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(content: Text('Lecture article à venir')),
            );
          },
          borderRadius: BorderRadius.circular(20),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Header (Category & Views)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    if (article['category_name'] != null)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: catColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: catColor.withOpacity(0.2)),
                        ),
                        child: Row(
                          children: [
                            // Icon(Icons.folder, size: 12, color: catColor),
                            // SizedBox(width: 4),
                            Text(
                              article['category_name'],
                              style: TextStyle(color: catColor, fontSize: 11, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      )
                    else 
                       const SizedBox(),
                       
                    Row(
                      children: [
                        Icon(Icons.visibility, size: 14, color: Colors.grey[500]),
                        const SizedBox(width: 4),
                        Text(
                          (article['views'] ?? 0).toString(),
                          style: TextStyle(color: Colors.grey[500], fontSize: 12),
                        ),
                      ],
                    ),
                  ],
                ),
                
                const SizedBox(height: 16),
                
                // Title
                Text(
                  article['title'] ?? 'Sans titre',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    height: 1.3,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                
                const SizedBox(height: 12),
                
                // Preview
                Expanded(
                  child: Text(
                    article['preview'] ?? '',
                    style: TextStyle(color: Colors.grey[400], fontSize: 13, height: 1.5),
                    maxLines: 4,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                
                const SizedBox(height: 16),
                
                // Footer (Author/Date/Score)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Mis à jour le ${(article['updated_at'] ?? article['created_at'] ?? '').toString().split(' ')[0]}',
                      style: TextStyle(color: Colors.grey[600], fontSize: 11),
                    ),
                    
                    if ((article['helpful_score'] ?? 0) > 0)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFF10B981).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          '${article['helpful_score']}% utile',
                          style: const TextStyle(color: Color(0xFF10B981), fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.search_off, size: 64, color: Colors.grey[700]),
          const SizedBox(height: 16),
          Text(
            'Aucun article trouvé',
            style: TextStyle(color: Colors.grey[400], fontSize: 18, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          Text(
            'Essayez une autre recherche',
            style: TextStyle(color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }

  Widget _buildPagination() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A).withOpacity(0.3),
        border: Border(top: BorderSide(color: Colors.white.withOpacity(0.1))),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: const Icon(Icons.chevron_left, color: Colors.white),
            onPressed: _currentPage > 1 ? () {
              setState(() => _currentPage--);
              _loadData();
            } : null,
          ),
          const SizedBox(width: 16),
          Text(
            'Page $_currentPage / $_totalPages',
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          ),
          const SizedBox(width: 16),
          IconButton(
            icon: const Icon(Icons.chevron_right, color: Colors.white),
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
