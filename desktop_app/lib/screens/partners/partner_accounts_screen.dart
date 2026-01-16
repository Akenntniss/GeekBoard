import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:geekboard_desktop/config/api_config.dart';
import 'package:geekboard_desktop/widgets/sidebar.dart';
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
      final url = Uri.parse('${ApiConfig.baseUrl}${ApiConfig.partnersListEndpoint}?search=$_searchQuery');
      final response = await http.get(url);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
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
    return Scaffold(
      backgroundColor: const Color(0xFF1a1a2e),
      body: Row(
        children: [
          const Sidebar(currentRoute: '/partenaires'),
          Expanded(
            child: Column(
              children: [
                _buildHeader(),
                Expanded(
                  child: _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : _buildContent(),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.all(24),
      color: const Color(0xFF16213e),
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
                  color: Colors.white,
                ),
              ),
              ElevatedButton.icon(
                onPressed: () {
                   // TODO: Show Add Partner Modal
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
              ),
              const SizedBox(width: 16),
              _buildStatCard(
                'Solde Total',
                '${_stats['total_balance'].toStringAsFixed(2)} €',
                Icons.account_balance_wallet,
                (_stats['total_balance'] ?? 0) >= 0 ? Colors.green : Colors.red,
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
                  style: const TextStyle(color: Colors.white),
                  decoration: InputDecoration(
                    hintText: 'Rechercher...',
                    hintStyle: TextStyle(color: Colors.white.withOpacity(0.5)),
                    prefixIcon: Icon(Icons.search, color: Colors.white.withOpacity(0.5)),
                    filled: true,
                    fillColor: Colors.white.withOpacity(0.1),
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

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
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
                  color: Colors.white.withOpacity(0.7),
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

  Widget _buildContent() {
    if (_partners.isEmpty) {
      return Center(
        child: Text(
          'Aucun partenaire trouvé',
          style: TextStyle(color: Colors.white.withOpacity(0.5)),
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

  const _PartnerCard({
    required this.partner,
    required this.onHistory,
    required this.onTransaction,
    required this.onLink,
  });

  @override
  Widget build(BuildContext context) {
    final solde = (partner['solde_actuel'] as num).toDouble();
    final isPositive = solde >= 0;

    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF16213e),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
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
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        'ID: ${partner['id']}',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.5),
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
          
          const Divider(height: 1, color: Colors.white10),
          
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
          
          const Divider(height: 1, color: Colors.white10),
          
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
        Icon(icon, size: 14, color: Colors.white.withOpacity(0.5)),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: TextStyle(
              color: Colors.white.withOpacity(0.7),
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
