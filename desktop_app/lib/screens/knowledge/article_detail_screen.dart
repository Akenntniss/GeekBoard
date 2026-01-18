import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_widget_from_html/flutter_widget_from_html.dart';
import '../../widgets/app_shell.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../config/api_config.dart';

class ArticleDetailScreen extends StatefulWidget {
  final int articleId;

  const ArticleDetailScreen({super.key, required this.articleId});

  @override
  State<ArticleDetailScreen> createState() => _ArticleDetailScreenState();
}

class _ArticleDetailScreenState extends State<ArticleDetailScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _article;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadArticle();
  }

  Future<void> _loadArticle() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.get(
        ApiConfig.knowledgeDetailEndpoint, 
        {'id': widget.articleId.toString()}
      );
      
      if (mounted) {
        setState(() {
          _article = response['article'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9);
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final textColor = isDark ? Colors.white : const Color(0xFF1E293B);

    return AppShell(
      currentRoute: '/knowledge',
      content: Scaffold(
        backgroundColor: bgColor,
        appBar: AppBar(
          backgroundColor: cardColor,
          elevation: 0,
          leading: IconButton(
            icon: Icon(Icons.arrow_back, color: textColor),
            onPressed: () => Navigator.of(context).pop(),
          ),
          title: Text(
            _isLoading ? 'Chargement...' : (_article?['title'] ?? 'Article'),
            style: TextStyle(color: textColor, fontWeight: FontWeight.bold),
          ),
          actions: [
            if (_article != null)
              IconButton(
                icon: const Icon(Icons.share, color: Colors.blue),
                onPressed: () {
                   ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Partage non disponible')),
                  );
                },
              ),
          ],
        ),
        body: _buildBody(isDark, cardColor, textColor),
      ),
    );
  }

  Widget _buildBody(bool isDark, Color cardColor, Color textColor) {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 48),
            const SizedBox(height: 16),
            Text('Erreur: $_error', style: TextStyle(color: textColor)),
            const SizedBox(height: 16),
            ElevatedButton(onPressed: _loadArticle, child: const Text('Réessayer')),
          ],
        ),
      );
    }

    if (_article == null) {
      return Center(child: Text('Article introuvable', style: TextStyle(color: textColor)));
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Container(
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: cardColor,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
             BoxShadow(
                color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
                blurRadius: 20,
                offset: const Offset(0, 10),
             ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Category Badge
            if (_article!['category_name'] != null)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  _article!['category_name'],
                  style: const TextStyle(color: Colors.blue, fontWeight: FontWeight.bold, fontSize: 13),
                ),
              ),
            
            const SizedBox(height: 24),

            // Title
            SelectableText(
              _article!['title'],
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.w800,
                color: textColor,
                height: 1.2,
                letterSpacing: -0.5,
              ),
            ),

            const SizedBox(height: 16),

            // Meta Row
            Row(
              children: [
                Icon(Icons.calendar_today, size: 14, color: Colors.grey[500]),
                const SizedBox(width: 6),
                Text(
                  (_article!['updated_at'] ?? _article!['created_at'] ?? '').toString(),
                  style: TextStyle(color: Colors.grey[500], fontSize: 13),
                ),
                const SizedBox(width: 24),
                Icon(Icons.visibility, size: 14, color: Colors.grey[500]),
                const SizedBox(width: 6),
                Text(
                  "${_article!['views'] ?? 0} vues",
                  style: TextStyle(color: Colors.grey[500], fontSize: 13),
                ),
              ],
            ),

            const SizedBox(height: 32),
            Divider(color: Colors.grey.withOpacity(0.2)),
            const SizedBox(height: 32),

            // Content (HTML)
            HtmlWidget(
              _article!['content'] ?? '',
              textStyle: TextStyle(
                color: textColor,
                fontSize: 16,
                height: 1.6,
              ),
              onTapUrl: (url) {
                 // Handle links if needed
                 return true; 
              },
            ),
             
            const SizedBox(height: 48),
            
            // Helpful Section
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF0F172A) : Colors.grey[50],
                borderRadius: BorderRadius.circular(16),
              ),
              child: Column(
                children: [
                   Text("Cet article vous a-t-il aidé ?", style: TextStyle(color: textColor, fontWeight: FontWeight.bold)),
                   const SizedBox(height: 16),
                   Row(
                     mainAxisAlignment: MainAxisAlignment.center,
                     children: [
                       ElevatedButton.icon(
                         onPressed: () {}, // Todo: Implement rating
                         icon: const Icon(Icons.thumb_up),
                         label: const Text("Oui"),
                         style: ElevatedButton.styleFrom(
                           backgroundColor: Colors.green,
                           foregroundColor: Colors.white,
                         ),
                       ),
                       const SizedBox(width: 16),
                       OutlinedButton.icon(
                         onPressed: () {}, // Todo
                         icon: const Icon(Icons.thumb_down),
                         label: const Text("Non"),
                         style: OutlinedButton.styleFrom(
                           foregroundColor: Colors.red,
                         ),
                       ),
                     ],
                   )
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
