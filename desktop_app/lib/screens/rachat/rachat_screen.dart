import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/app_shell.dart';
import 'package:geekboard_desktop/services/api_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import '../../widgets/rachat_filter_bar.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import 'package:printing/printing.dart';
import '../../services/police_book_service.dart';
import 'add_rachat_screen.dart';
import 'rachat_detail_dialog.dart';
import '../../services/rachat_pdf_service.dart';

class RachatScreen extends StatefulWidget {
  const RachatScreen({super.key});

  @override
  State<RachatScreen> createState() => _RachatScreenState();
}

class _RachatScreenState extends State<RachatScreen> {
  ApiService get _apiService => context.read<AuthService>().getApiService();
  
  // Data
  List<Map<String, dynamic>> _rachats = [];
  
  // Pagination
  int _currentPage = 1;
  int _totalPages = 1;
  bool _isLoading = true;
  
  // Filters State
  String _search = '';
  // bool _showFunctional = false; // Not used yet in API
  // bool _showNonFunctional = false; // Not used yet in API

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    
    try {
      final queryParams = {
        'page': _currentPage.toString(),
        'limit': '50',
        'search': _search,
      };

      final response = await _apiService.get(ApiConfig.rachatListEndpoint, queryParams);
      
      if (mounted) {
        setState(() {
          if (response['rachats'] != null) {
            _rachats = List<Map<String, dynamic>>.from(response['rachats']);
          } else {
             _rachats = [];
          }
          
          if (response['pagination'] != null) {
            _totalPages = response['pagination']['totalPages'] ?? 1;
          }
        });
      }
    } catch (e) {
      print('Error loading rachats: $e');
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
    final textColor = isDark ? Colors.white : const Color(0xFF1E293B);
    final subTextColor = isDark ? Colors.grey[400] : Colors.grey[600];
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade300;

    return AppShell(
      currentRoute: '/rachat',
      content: Scaffold(
        backgroundColor: Colors.transparent, // Handled by AppShell or Theme
        body: Column(
          children: [
            // Header Stats 
            _buildHeader(isDark, textColor, subTextColor, borderColor),
            
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  children: [
                    // Filters
                    RachatFilterBar(
                      onFilterChanged: (search, isFunctional, isNonFunctional) {
                        setState(() {
                          _search = search;
                          _currentPage = 1;
                        });
                        _loadData();
                      },
                    ),
                    
                    // Table
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: cardColor,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: borderColor),
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
                          : _rachats.isEmpty 
                              ? _buildEmptyState(subTextColor)
                              : Column(
                                  children: [
                                    _buildTableHeader(isDark, borderColor),
                                    Expanded(
                                      child: ListView.separated(
                                        padding: EdgeInsets.zero,
                                        itemCount: _rachats.length,
                                        separatorBuilder: (ctx, i) => Divider(
                                          height: 1, 
                                          thickness: 1, 
                                          color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade200
                                        ),
                                        itemBuilder: (context, index) {
                                          return _buildRachatRow(_rachats[index], isDark, textColor, subTextColor);
                                        },
                                      ),
                                    ),
                                    _buildPagination(isDark, borderColor, textColor),
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

  Widget _buildHeader(bool isDark, Color textColor, Color? subTextColor, Color borderColor) {
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
                    colors: [Color(0xFF6366f1), Color(0xFF8b5cf6)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.history, color: Colors.white, size: 24),
              ),
              const SizedBox(width: 16),
              Text(
                'Historique des rachats',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: textColor,
                  letterSpacing: -0.5,
                ),
              ),
            ],
          ),
          
          Row(
            children: [
              OutlinedButton.icon(
                onPressed: _openLivrePolice,
                icon: Icon(Icons.menu_book, size: 18, color: textColor),
                label: Text('Livre de Police', style: TextStyle(color: textColor)),
                style: OutlinedButton.styleFrom(
                  side: BorderSide(color: borderColor),
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(width: 12),
              ElevatedButton.icon(
                onPressed: () async {
                  final result = await showDialog(
                    context: context,
                    builder: (context) => const AddRachatScreen(),
                  );
                  if (result == true) {
                    _loadData();
                  }
                },
                icon: const Icon(Icons.add, size: 18),
                label: const Text('Nouveau Rachat'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF6366f1),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 4,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTableHeader(bool isDark, Color borderColor) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A).withOpacity(0.5) : Colors.grey.shade50,
        border: Border(bottom: BorderSide(color: borderColor)),
        borderRadius: const BorderRadius.vertical(top: Radius.circular(16)), // Clip top corners
      ),
      child: Row(
        children: [
          Expanded(flex: 2, child: _buildHeaderCell('CLIENT', isDark)), 
          Expanded(flex: 1, child: _buildHeaderCell('DATE', isDark)),
          Expanded(flex: 2, child: _buildHeaderCell('APPAREIL', isDark)),
          Expanded(flex: 2, child: _buildHeaderCell('IMEI / SIN', isDark)),
          Expanded(flex: 1, child: _buildHeaderCell('PRIX', isDark)), 
          const SizedBox(width: 50),
        ],
      ),
    );
  }

  Widget _buildHeaderCell(String text, bool isDark) {
    return Text(
      text,
      style: TextStyle(
        color: isDark ? Colors.grey[500] : Colors.grey[600],
        fontSize: 11,
        fontWeight: FontWeight.bold,
        letterSpacing: 1,
      ),
    );
  }

  Widget _buildRachatRow(Map<String, dynamic> rachat, bool isDark, Color textColor, Color? subTextColor) {
    return InkWell(
      onTap: () => _showRachatDetails(rachat),
      hoverColor: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
        child: Row(
        children: [
          // Client
          Expanded(
            flex: 2,
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(colors: [Color(0xFFe0e7ff), Color(0xFFc7d2fe)]),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Center(
                    child: Text(
                      (rachat['client_prenom'] ?? 'C').toString().substring(0, 1).toUpperCase(),
                      style: const TextStyle(color: Color(0xFF4338ca), fontWeight: FontWeight.bold),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${rachat['client_prenom']} ${rachat['client_nom']}',
                        style: TextStyle(color: textColor, fontWeight: FontWeight.w600, fontSize: 13),
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        rachat['telephone'] ?? '',
                        style: TextStyle(color: subTextColor, fontSize: 11),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          
          // Date
          Expanded(
            flex: 1,
            child: Text(
              rachat['date_formatted'] ?? '-',
              style: TextStyle(color: subTextColor, fontSize: 12),
            ),
          ),
          
          // Apple
          Expanded(
            flex: 2,
            child: Text(
              rachat['modele'] ?? '-',
              style: TextStyle(color: textColor, fontWeight: FontWeight.w500),
            ),
          ),
          
          // IMEI
          Expanded(
            flex: 2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: isDark ? Colors.black.withOpacity(0.2) : Colors.grey.shade100,
                borderRadius: BorderRadius.circular(4),
                border: isDark ? null : Border.all(color: Colors.grey.shade300),
              ),
              child: Text(
                rachat['sin'] ?? '-',
                style: TextStyle(color: subTextColor, fontSize: 11, fontFamily: 'monospace'),
              ),
            ),
          ),
          
          // Price
          Expanded(
            flex: 1,
            child: Text(
              rachat['prix_formatted'] ?? '0.00 €',
              style: const TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold),
            ),
          ),
          
          // Actions
          SizedBox(
            width: 50,
            child: IconButton(
              icon: const Icon(Icons.print, color: Colors.blue, size: 20),
              tooltip: 'Imprimer Attestation',
                onPressed: () {
                 _printAttestation(rachat);
              },
            ),
          ),
        ],
      ),
    ));
  }
  
  Future<void> _printAttestation(Map<String, dynamic> rachat) async {
    try {
      final shopName = context.read<AuthService>().currentShop?.name ?? 'GEEKBOARD';
      final shopInfo = {'name': shopName}; // Add more info if available in auth service
      
      final pdfBytes = await RachatPdfService.generateCertificate(
        rachat: rachat,
        shopInfo: shopInfo,
      );
      
      await Printing.layoutPdf(
        onLayout: (format) => pdfBytes,
        name: 'Attestation_Rachat_${rachat['id']}.pdf',
      );
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur impression: $e'), backgroundColor: Colors.red));
      }
    }
  }

  void _showRachatDetails(Map<String, dynamic> rachat) {
    showDialog(
      context: context,
      builder: (context) => RachatDetailDialog(rachat: rachat),
    );
  }

  Widget _buildEmptyState(Color? textColor) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.history_toggle_off, size: 64, color: Colors.grey[700]),
          const SizedBox(height: 16),
          Text(
            'Aucun rachat trouvé',
            style: TextStyle(color: textColor, fontSize: 18, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildPagination(bool isDark, Color borderColor, Color textColor) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A).withOpacity(0.3) : Colors.grey.shade50,
        border: Border(top: BorderSide(color: borderColor)),
        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          IconButton(
            icon: Icon(Icons.chevron_left, color: textColor),
            onPressed: _currentPage > 1 ? () {
              setState(() => _currentPage--);
              _loadData();
            } : null,
          ),
          const SizedBox(width: 16),
          Text(
            'Page $_currentPage / $_totalPages',
            style: TextStyle(color: textColor, fontWeight: FontWeight.bold),
          ),
          const SizedBox(width: 16),
          IconButton(
            icon: Icon(Icons.chevron_right, color: textColor),
            onPressed: _currentPage < _totalPages ? () {
              setState(() => _currentPage++);
              _loadData();
            } : null,
          ),
        ],
      ),
    );
  }

  Future<void> _openLivrePolice() async {
    // Demander l'année
    int? year = await showDialog<int>(
      context: context,
      builder: (context) {
        final controller = TextEditingController(text: DateTime.now().year.toString());
        return AlertDialog(
          title: const Text("Export Livre de Police"),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text("Veuillez saisir l'année à exporter :"),
              const SizedBox(height: 10),
              TextField(
                controller: controller,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: "Année", border: OutlineInputBorder()),
                onSubmitted: (_) { 
                  final y = int.tryParse(controller.text);
                  if (y != null && y > 2000 && y < 2100) Navigator.pop(context, y);
                },
              ),
            ],
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text("Annuler")),
            ElevatedButton(
              onPressed: () {
                final y = int.tryParse(controller.text);
                if (y != null && y > 2000 && y < 2100) {
                  Navigator.pop(context, y);
                }
              },
              child: const Text("Générer"),
            ),
          ],
        );
      },
    );

    if (year == null) return;

    setState(() => _isLoading = true);

    try {
      // 1. Charger les données (limite -1 pour tout avoir)
      final api = context.read<AuthService>().getApiService();
      final response = await api.get(ApiConfig.rachatListEndpoint, {
        'year': year.toString(),
        'limit': '-1',
        'order': 'asc' // Chronologique pour le livre
      });

      final List<Map<String, dynamic>> rachats = 
          List<Map<String, dynamic>>.from(response['rachats'] ?? []);

      if (rachats.isEmpty) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Aucun rachat trouvé pour $year')));
        return;
      }

      // 2. Info magasin
      final shopName = context.read<AuthService>().currentShop?.name ?? 'GEEKBOARD';
      final shopInfo = {'name': shopName};

      // 3. Générer PDF
      final pdfBytes = await PoliceBookService.generatePoliceBook(
        rachats: rachats,
        year: year,
        shopInfo: shopInfo,
      );

      // 4. Afficher Preview
      await Printing.layoutPdf(
        onLayout: (format) => pdfBytes,
        name: 'Livre_Police_$year.pdf',
      );

    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur génération PDF: $e'), backgroundColor: Colors.red));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }
}
