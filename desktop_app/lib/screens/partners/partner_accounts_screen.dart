import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:provider/provider.dart';
import 'package:geekboard_desktop/services/auth_service.dart';
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';
import '../../widgets/app_shell.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:geekboard_desktop/screens/partners/dialogs/add_partner_dialog.dart';
import 'package:geekboard_desktop/screens/partners/dialogs/transaction_dialog.dart';
import 'package:geekboard_desktop/screens/partners/dialogs/history_dialog.dart';
import 'package:geekboard_desktop/screens/partners/dialogs/link_dialog.dart';

class PartnerAccountsScreen extends StatefulWidget {
  const PartnerAccountsScreen({super.key});

  @override
  State<PartnerAccountsScreen> createState() => _PartnerAccountsScreenState();
}

class _PartnerAccountsScreenState extends State<PartnerAccountsScreen> {
  bool _isLoading = true;
  List<dynamic> _partners = [];
  Map<String, dynamic> _stats = {
    'active_partners': 0,
    'total_balance': 0.0,
  };
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchPartners();
  }

  Future<void> _fetchPartners() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final response = await apiService.get(ApiConfig.partnersListEndpoint, {'search': _searchQuery}); // ApiService handles URL building and Headers

      if (true) { // ApiService throws on error, so if we are here, it's success (usually)
        final data = response; // ApiService returns decoded JSON Map
        if (true) { // ApiService returns data directly, success check usually inside apiService or implicit
          setState(() {
            _partners = data['partners'];
            _stats = data['stats'];
            _isLoading = false;
          });
        }
      } else {
        throw Exception('Failed to load partners');
      }
    } catch (e) {
      setState(() => _isLoading = false);
      _showError('Erreur de chargement: $e');
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: Colors.red,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDark ? Colors.white : const Color(0xFF1E293B);
    final subTextColor = isDark ? Colors.white.withOpacity(0.7) : Colors.grey.shade600;
    final cardColor = isDark ? const Color(0xFF16213e) : Colors.white; // Using 16213e which was original dark bg for partners
    final borderColor = isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade300;
    
    // Pour le TextField
    final inputFillColor = isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200;
    final placeholderColor = isDark ? Colors.white.withOpacity(0.5) : Colors.grey.shade500;

    return AppShell(
      currentRoute: '/partenaires',
      content: Scaffold(
        backgroundColor: Colors.transparent,
        body: Column(
          children: [
            _buildHeader(isDark, textColor, subTextColor, inputFillColor, placeholderColor),
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _buildContent(isDark, cardColor, borderColor, textColor, subTextColor),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(bool isDark, Color textColor, Color subTextColor, Color inputFillColor, Color placeholderColor) {
    return Container(
      padding: const EdgeInsets.all(24),
      color: isDark ? const Color(0xFF16213e) : Colors.white, // Keep header distinctive? Or merge? Let's keep it styled.
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Comptes Partenaires',
                style: GoogleFonts.poppins(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: textColor,
                ),
              ),
              ElevatedButton.icon(
                onPressed: () {
                   _showAddPartnerDialog();
                },
                icon: const Icon(Icons.person_add),
                label: const Text('Nouveau Partenaire'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF4f46e5),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              _buildStatCard(
                'Partenaires Actifs',
                _stats['active_partners'].toString(),
                Icons.people,
                Colors.blue,
                isDark,
              ),
              const SizedBox(width: 16),
              _buildStatCard(
                'Solde Total',
                '${_stats['total_balance'].toStringAsFixed(2)} €',
                Icons.account_balance_wallet,
                (_stats['total_balance'] ?? 0) >= 0 ? Colors.green : Colors.red,
                isDark,
              ),
              const Spacer(),
              SizedBox(
                width: 300,
                child: TextField(
                  controller: _searchController,
                  onChanged: (value) {
                    setState(() => _searchQuery = value);
                    // Debounce logic could be added here
                    _fetchPartners();
                  },
                  style: TextStyle(color: textColor),
                  decoration: InputDecoration(
                    hintText: 'Rechercher...',
                    hintStyle: TextStyle(color: placeholderColor),
                    prefixIcon: Icon(Icons.search, color: placeholderColor),
                    filled: true,
                    fillColor: inputFillColor,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color, bool isDark) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey[300]!),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: color.withOpacity(0.2),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 24),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: TextStyle(
                  color: isDark ? Colors.white.withOpacity(0.7) : Colors.grey[600],
                  fontSize: 14,
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  color: color,
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildContent(bool isDark, Color cardColor, Color borderColor, Color textColor, Color subTextColor) {
    if (_partners.isEmpty) {
      return Center(
        child: Text(
          'Aucun partenaire trouvé',
          style: TextStyle(color: subTextColor),
        ),
      );
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: LayoutBuilder(
        builder: (context, constraints) {
          return GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: constraints.maxWidth > 1400 ? 4 : (constraints.maxWidth > 1000 ? 3 : 2),
              childAspectRatio: 1.5,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
            ),
            itemCount: _partners.length,
            itemBuilder: (context, index) {
              return _PartnerCard(
                partner: _partners[index],
                onHistory: () => _showHistoryDialog(_partners[index]),
                onTransaction: () => _showTransactionDialog(_partners[index]),
                onLink: () => _showLinkDialog(_partners[index]),
                isDark: isDark,
                cardColor: cardColor,
                borderColor: borderColor,
                textColor: textColor,
                subTextColor: subTextColor,
              );
            },
          );
        },
      ),
    );
  }
  
  void _showAddPartnerDialog() {
    showDialog(
      context: context,
      builder: (context) => AddPartnerDialog(onSuccess: _fetchPartners),
    );
  }

  void _showHistoryDialog(dynamic partner) {
    showDialog(
      context: context,
      builder: (context) => HistoryDialog(partner: partner),
    ).then((_) => _fetchPartners()); // Refresh balance after modal close
  }

  void _showTransactionDialog(dynamic partner) {
    showDialog(
      context: context,
      builder: (context) => TransactionDialog(
        partner: partner, 
        onSuccess: _fetchPartners,
      ),
    );
  }

  void _showLinkDialog(dynamic partner) {
    showDialog(
      context: context,
      builder: (context) => LinkDialog(partner: partner),
    );
  }
}

class _PartnerCard extends StatelessWidget {
  final dynamic partner;
  final VoidCallback onHistory;
  final VoidCallback onTransaction;
  final VoidCallback onLink;
  final bool isDark;
  final Color cardColor;
  final Color borderColor;
  final Color textColor;
  final Color subTextColor;

  const _PartnerCard({
    required this.partner,
    required this.onHistory,
    required this.onTransaction,
    required this.onLink,
    required this.isDark,
    required this.cardColor,
    required this.borderColor,
    required this.textColor,
    required this.subTextColor,
  });

  @override
  Widget build(BuildContext context) {
    final solde = (partner['solde_actuel'] as num).toDouble();
    final isPositive = solde >= 0;

    return Container(
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        partner['nom'],
                        style: TextStyle(
                          color: textColor,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        'ID: ${partner['id']}',
                        style: TextStyle(
                          color: subTextColor,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: (partner['actif'] == true) 
                        ? Colors.green.withOpacity(0.2) 
                        : Colors.red.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: (partner['actif'] == true) ? Colors.green : Colors.red,
                    ),
                  ),
                  child: Text(
                    (partner['actif'] == true) ? 'Actif' : 'Inactif',
                    style: TextStyle(
                      color: (partner['actif'] == true) ? Colors.green : Colors.red,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          Divider(height: 1, color: isDark ? Colors.white10 : Colors.grey[200]),
          
          // Body
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildInfoRow(Icons.email, partner['email'] ?? 'N/A'),
                  const SizedBox(height: 8),
                  _buildInfoRow(Icons.phone, partner['telephone'] ?? 'N/A'),
                  const Spacer(),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: isPositive 
                          ? Colors.green.withOpacity(0.1) 
                          : Colors.red.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(
                        color: isPositive ? Colors.green : Colors.red,
                        width: 1,
                      ),
                    ),
                    child: Text(
                      '${solde > 0 ? '+' : ''}${solde.toStringAsFixed(2)} €',
                      style: TextStyle(
                        color: isPositive ? Colors.green : Colors.red,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ],
              ),
            ),
          ),
          
          Divider(height: 1, color: isDark ? Colors.white10 : Colors.grey[200]),
          
          // Actions
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildActionButton(
                  icon: Icons.history,
                  color: Colors.blue,
                  onPressed: onHistory,
                  tooltip: 'Historique',
                ),
                _buildActionButton(
                  icon: Icons.link,
                  color: Colors.orange,
                  onPressed: onLink,
                  tooltip: 'Envoyer Lien',
                ),
                _buildActionButton(
                  icon: Icons.add,
                  color: const Color(0xFF4f46e5),
                  onPressed: onTransaction,
                  tooltip: 'Nouvelle Transaction',
                  isPrimary: true,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 14, color: subTextColor),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: subTextColor,
              fontSize: 12,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ),
      ],
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required Color color,
    required VoidCallback onPressed,
    required String tooltip,
    bool isPrimary = false,
  }) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onPressed,
          borderRadius: BorderRadius.circular(8),
          child: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(
                color: color.withOpacity(0.3),
              ),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
        ),
      ),
    );
  }
}
