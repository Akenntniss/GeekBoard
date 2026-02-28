import 'package:flutter/material.dart';
import '../../theme/macos_theme.dart';
import 'package:intl/intl.dart';

import '../../services/auth_service.dart';
import '../../services/api_service.dart';
import '../../config/api_config.dart';
import 'package:provider/provider.dart';

class CommandDetailDialog extends StatefulWidget {
  final Map<String, dynamic> command;
  final ApiService apiService;
  final VoidCallback? onUpdate;

  const CommandDetailDialog({
    super.key, 
    required this.command, 
    required this.apiService,
    this.onUpdate,
  });

  @override
  State<CommandDetailDialog> createState() => _CommandDetailDialogState();
}

class _CommandDetailDialogState extends State<CommandDetailDialog> {
  late Map<String, dynamic> _command;
  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _command = Map<String, dynamic>.from(widget.command);
    print('DEBUG COMMAND DETAIL: $_command');
  }

  Future<void> _updateStatus(String newStatus) async {
    if (_isLoading) return;
    
    setState(() => _isLoading = true);
    try {
      // Note: L'API attend probablement une mise à jour via un endpoint dédié ou générique
      // Je vais utiliser un endpoint générique de mise à jour de commande si dispo, 
      // sinon je devrai peut-être en créer un. Pour l'instant on tente un POST vers update
      await widget.apiService.post(ApiConfig.commandesUpdateEndpoint, {
        'id': _command['id'],
        'statut': newStatus,
      });

      if (mounted) {
        setState(() {
          _command['statut'] = newStatus;
          _isLoading = false;
        });
        widget.onUpdate?.call();
        if (mounted) {
           Navigator.of(context).pop();
           ScaffoldMessenger.of(context).showSnackBar(
             const SnackBar(content: Text('Statut mis à jour avec succès'), backgroundColor: Colors.green),
           );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isLoading = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Future<void> _sendDelaySms() async {
    // Template ID 22 corresponds to "Retard Livraison"
    const int templateId = 22;
    
    // Determine context (Repair or Command)
    final int? reparationId = _command['reparation_id'] != null 
        ? int.tryParse(_command['reparation_id'].toString()) 
        : null;
    final int? commandId = _command['id'] != null 
        ? int.tryParse(_command['id'].toString()) 
        : null;

    try {
      // Pass both, the API service will handle which one to send
      await widget.apiService.sendSmsTemplate(
        reparationId, 
        templateId,
        commandId: commandId
      );
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('SMS de retard envoyé avec succès'), backgroundColor: Colors.green),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur envoi SMS: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Dialog(
      backgroundColor: isDark ? MacOSTheme.gray800 : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        width: 650,
        constraints: const BoxConstraints(maxHeight: 800),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header with MacOS Blue Gradient
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF007AFF), Color(0xFF5AC8FA)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.only(
                  topLeft: Radius.circular(20),
                  topRight: Radius.circular(20),
                ),
              ),
              child: Row(
                children: [
                  const Icon(Icons.shopping_bag, color: Colors.white, size: 28),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Commande #${_command['id']}',
                          style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                        ),
                        Text(
                          _command['reference'] ?? 'Sans référence',
                          style: TextStyle(color: Colors.white.withOpacity(0.8), fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close, color: Colors.white),
                  ),
                ],
              ),
            ),

            if (_isLoading) const LinearProgressIndicator(),

            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Status Selection Grid
                    _buildSectionHeader('ACTIONS RAPIDES'),
                    const SizedBox(height: 16),
                    // Action Buttons Row
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton.icon(
                            onPressed: _sendDelaySms,
                            icon: const Icon(Icons.access_time_rounded, size: 18),
                            label: const Text('Signaler un retard'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: Colors.orange,
                              side: BorderSide(color: Colors.orange.withOpacity(0.5)),
                              padding: const EdgeInsets.symmetric(vertical: 12),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    _buildSectionHeader('CHANGER LE STATUT'),
                    const SizedBox(height: 16),
                    _buildStatusGrid(),
                    
                    const SizedBox(height: 32),
                    
                    _buildSectionHeader('INFORMATIONS PIÈCE'),
                    const SizedBox(height: 16),
                    _buildInfoRow('Pièce', _command['nom_piece'] ?? 'Non spécifié', isLarge: true),
                    _buildInfoRow('Référence / SKU', _command['code_barre'] ?? 'Non spécifié'),
                    _buildInfoRow('Fournisseur', _command['fournisseur_nom'] ?? 'Non spécifié'),
                    _buildInfoRow('Quantité', _command['quantite']?.toString() ?? '1'),
                    _buildInfoRow('Prix Estimé', '${_command['prix_estime'] ?? '0.00'} €'),
                    
                    const SizedBox(height: 32),
                    _buildSectionHeader('DÉTAILS CLIENT'),
                    const SizedBox(height: 16),
                    _buildInfoRow('Client', '${_command['client_prenom'] ?? ''} ${_command['client_nom'] ?? ''}'),
                    _buildInfoRow('Appareil', '${_command['type_appareil'] ?? ''} ${_command['modele'] ?? ''}'),
                    _buildInfoRow('Date Commande', _formatDate(_command['date_creation'])),
                  ],
                ),
              ),
            ),
            
            // Footer
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: isDark ? Colors.black.withOpacity(0.2) : Colors.grey.shade50,
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(20),
                  bottomRight: Radius.circular(20),
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: Text('Fermer', style: TextStyle(color: isDark ? Colors.white70 : Colors.black54)),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusGrid() {
    final statuses = [
      {'code': 'en_attente', 'label': 'En attente', 'color': Colors.orange, 'icon': Icons.hourglass_empty},
      {'code': 'commande', 'label': 'Commandé', 'color': Colors.cyan, 'icon': Icons.local_shipping},
      {'code': 'recue', 'label': 'Reçu', 'color': Colors.green, 'icon': Icons.check_circle},
      {'code': 'utilise', 'label': 'Utilisé', 'color': Colors.indigo, 'icon': Icons.build},
      {'code': 'annulee', 'label': 'Annulé', 'color': Colors.red, 'icon': Icons.cancel},
      {'code': 'a_retourner', 'label': 'À retourner', 'color': Colors.grey, 'icon': Icons.undo},
    ];

    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 10,
        mainAxisSpacing: 10,
        childAspectRatio: 2.5,
      ),
      itemCount: statuses.length,
      itemBuilder: (context, index) {
        final s = statuses[index];
        final isSelected = _command['statut'] == s['code'];
        final color = s['color'] as Color;

        return InkWell(
          onTap: () => _updateStatus(s['code'] as String),
          borderRadius: BorderRadius.circular(12),
          child: Container(
            decoration: BoxDecoration(
              color: isSelected ? color : color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(
                color: isSelected ? color : color.withOpacity(0.3),
                width: 2,
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  s['icon'] as IconData,
                  size: 16,
                  color: isSelected ? Colors.white : color,
                ),
                const SizedBox(width: 8),
                Text(
                  s['label'] as String,
                  style: TextStyle(
                    color: isSelected ? Colors.white : color,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildSectionHeader(String title) {
    return Text(
      title,
      style: const TextStyle(
        color: Color(0xFF007AFF),
        fontSize: 12,
        fontWeight: FontWeight.bold,
        letterSpacing: 1.2,
      ),
    );
  }

  Widget _buildInfoRow(String label, String value, {bool isLarge = false}) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                color: isDark ? Colors.white54 : Colors.black54,
                fontSize: 13,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                color: isDark ? Colors.white : Colors.black87,
                fontSize: isLarge ? 15 : 13,
                fontWeight: isLarge ? FontWeight.bold : FontWeight.normal,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(dynamic dateValue) {
    if (dateValue == null) return '--/--';
    try {
      final date = DateTime.parse(dateValue.toString());
      return DateFormat('dd/MM/yyyy à HH:mm').format(date);
    } catch (_) {
      return dateValue.toString();
    }
  }
}
