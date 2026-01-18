import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';
import '../config/api_config.dart';
import '../theme/macos_theme.dart';

class QuoteDetailModal extends StatefulWidget {
  final int devisId;
  final VoidCallback? onUpdate;

  const QuoteDetailModal({Key? key, required this.devisId, this.onUpdate}) : super(key: key);

  @override
  State<QuoteDetailModal> createState() => _QuoteDetailModalState();
}

class _QuoteDetailModalState extends State<QuoteDetailModal> {
  bool _isLoading = true;
  Map<String, dynamic>? _devis;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadDetails();
  }

  Future<void> _loadDetails() async {
    setState(() => _isLoading = true);
    try {
      final authService = context.read<AuthService>();
      final apiService = authService.getApiService();
      final shopId = authService.currentUser?.shopId;
      String url = '${ApiConfig.devisDetailsEndpoint}?id=${widget.devisId}';
      if (shopId != null) url += '&shop_id=$shopId';
      
      final response = await apiService.get(url);
      
      if (mounted) {
        setState(() {
          _devis = response['devis'];
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() { _error = e.toString(); _isLoading = false; });
    }
  }

  Future<void> _resendQuote() async {
    // Single quote resend implementation would go here (using existing logic or new endpoint)
    // The user asked for "Resend All" button on main screen, but potentially wants single resend here too.
    // For now, mirroring the "View" modal mostly.
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final bgColor = isDark ? const Color(0xFF1E1E1E) : Colors.white;
    final textColor = isDark ? Colors.white : Colors.black87;

    return Dialog(
       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
       child: Container(
         width: 800,
         constraints: const BoxConstraints(maxHeight: 800),
         decoration: BoxDecoration(
           color: bgColor,
           borderRadius: BorderRadius.circular(16),
         ),
         child: Column(
           children: [
             // Header
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
                 borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
               ),
               child: Row(
                 children: [
                   const Icon(Icons.description, color: Colors.blue, size: 24),
                   const SizedBox(width: 12),
                   Expanded(
                     child: Column(
                       crossAxisAlignment: CrossAxisAlignment.start,
                       children: [
                         Text(
                           "Devis #${_devis?['numero'] ?? widget.devisId}",
                           style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: textColor),
                         ),
                         if (_devis != null)
                           Text(
                             _getStatutLabel(_devis!['statut']),
                             style: TextStyle(
                               fontSize: 12, 
                               color: _getStatutColor(_devis!['statut']),
                               fontWeight: FontWeight.w600
                             ),
                           ),
                       ],
                     ),
                   ),
                   if (_devis != null && _devis!['total_ht'] != null)
                     Text(
                       "${double.parse(_devis!['total_ht'].toString()).toStringAsFixed(2)} € HT",
                       style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.green),
                     ),
                   const SizedBox(width: 16),
                   IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                 ],
               ),
             ),

             // Body
             Expanded(
               child: _isLoading 
                 ? const Center(child: CircularProgressIndicator())
                 : _error != null 
                   ? Center(child: Text("Erreur: $_error", style: const TextStyle(color: Colors.red)))
                   : SingleChildScrollView(
                       padding: const EdgeInsets.all(24),
                       child: Column(
                         crossAxisAlignment: CrossAxisAlignment.start,
                         children: [
                           // Client & Device Info Row
                           Row(
                             crossAxisAlignment: CrossAxisAlignment.start,
                             children: [
                               // Client Info
                               Expanded(
                                 child: _buildInfoCard(
                                   context,
                                   title: "Informations Client",
                                   icon: Icons.person,
                                   content: Column(
                                     crossAxisAlignment: CrossAxisAlignment.start,
                                     children: [
                                       Text("${_devis!['client_prenom'] ?? ''} ${_devis!['client_nom'] ?? ''}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                       const SizedBox(height: 8),
                                       if (_devis!['client_telephone'] != null)
                                         _buildIconText(Icons.phone, _devis!['client_telephone'], Colors.green),
                                       if (_devis!['client_email'] != null)
                                          _buildIconText(Icons.email, _devis!['client_email'], Colors.blue),
                                     ],
                                   ),
                                 ),
                               ),
                               const SizedBox(width: 20),
                               // Repair Info
                               Expanded(
                                 child: _buildInfoCard(
                                   context,
                                   title: "Réparation #${_devis!['reparation_id']}",
                                   icon: Icons.build,
                                   content: Column(
                                     crossAxisAlignment: CrossAxisAlignment.start,
                                     children: [
                                       Text("${_devis!['reparation_marque'] ?? ''} ${_devis!['reparation_modele'] ?? ''}", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                       const SizedBox(height: 8),
                                       if (_devis!['reparation_probleme'] != null)
                                          _buildIconText(Icons.warning, _devis!['reparation_probleme'], Colors.orange),
                                     ],
                                   ),
                                 ),
                               ),
                             ],
                           ),
                           const SizedBox(height: 20),

                           // Solutions
                           if (_devis!['solutions'] != null && (_devis!['solutions'] as List).isNotEmpty) ...[
                             const Text("Solutions Proposées", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                             const SizedBox(height: 12),
                             ...(_devis!['solutions'] as List).map((sol) => Container(
                               margin: const EdgeInsets.only(bottom: 16),
                               padding: const EdgeInsets.all(16),
                               decoration: BoxDecoration(
                                 border: Border.all(color: Theme.of(context).dividerColor),
                                 borderRadius: BorderRadius.circular(12),
                               ),
                               child: Column(
                                 crossAxisAlignment: CrossAxisAlignment.start,
                                 children: [
                                   Row(
                                     mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                     children: [
                                       Text(sol['nom'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                                       Text("${double.parse(sol['prix_total'].toString()).toStringAsFixed(2)} €", style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                                     ],
                                   ),
                                   if (sol['description'] != null) ...[
                                      const SizedBox(height: 8),
                                      Text(sol['description'], style: TextStyle(color: textColor.withOpacity(0.7), fontSize: 13)),
                                   ],
                                   if (sol['elements'] != null) ...[
                                      const SizedBox(height: 12),
                                      const Divider(),
                                      ... (sol['elements'] as List).map((el) => Padding(
                                        padding: const EdgeInsets.symmetric(vertical: 4),
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Text(el['nom'], style: const TextStyle(fontSize: 13)),
                                            Text("${double.parse(el['prix'].toString()).toStringAsFixed(2)} €", style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w500)),
                                          ],
                                        ),
                                      )),
                                   ]
                                 ],
                               ),
                             )).toList(),
                             const SizedBox(height: 20),
                           ],

                           // Logs
                           if (_devis!['logs'] != null && (_devis!['logs'] as List).isNotEmpty) ...[
                             const Text("Historique", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                             const SizedBox(height: 12),
                             ListView.builder(
                               shrinkWrap: true,
                               physics: const NeverScrollableScrollPhysics(),
                               itemCount: (_devis!['logs'] as List).length,
                               itemBuilder: (ctx, i) {
                                 final log = _devis!['logs'][i];
                                 return ListTile(
                                   dense: true,
                                   leading: const Icon(Icons.history, size: 16, color: Colors.grey),
                                   title: Text(log['action'], style: const TextStyle(fontWeight: FontWeight.w600)),
                                   subtitle: Text(log['description'] ?? ''),
                                   trailing: Text(
                                     log['date_action'] != null ? DateFormat('dd/MM HH:mm').format(DateTime.parse(log['date_action'])) : '',
                                     style: TextStyle(fontSize: 12, color: textColor.withOpacity(0.5)),
                                   ),
                                 );
                               },
                             ),
                           ],
                         ],
                       ),
                   ),
             ),
           ],
         ),
       ),
    );
  }

  Widget _buildInfoCard(BuildContext context, {required String title, required IconData icon, required Widget content}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.withOpacity(0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? Colors.white10 : Colors.black12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 16, color: Colors.blue),
              const SizedBox(width: 8),
              Text(title, style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: isDark ? Colors.white60 : Colors.black54)),
            ],
          ),
          const SizedBox(height: 12),
          content,
        ],
      ),
    );
  }

  Widget _buildIconText(IconData icon, String text, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 8),
          Text(text, style: const TextStyle(fontSize: 13)),
        ],
      ),
    );
  }

  String _getStatutLabel(String? status) {
    switch (status) {
      case 'envoye': return 'En Attente';
      case 'accepte': return 'Accepté';
      case 'refuse': return 'Refusé';
      case 'brouillon': return 'Brouillon';
      case 'expire': return 'Expiré';
      default: return status ?? 'Inconnu';
    }
  }

  Color _getStatutColor(String? status) {
    switch (status) {
      case 'envoye': return Colors.orange;
      case 'accepte': return Colors.green;
      case 'refuse': return Colors.red;
      case 'expire': return Colors.grey;
      default: return Colors.blue;
    }
  }
}
