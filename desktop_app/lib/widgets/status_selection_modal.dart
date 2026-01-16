import 'package:flutter/material.dart';
import 'package:flutter/cupertino.dart';
import '../theme/macos_theme.dart';

class StatusSelectionModal extends StatefulWidget {
  final String categoryId;
  final String categoryName;
  final Map<String, dynamic> repair;
  final List<Map<String, dynamic>> statusOptions;
  final Function(String statusId, bool sendSms) onStatusSelected;

  const StatusSelectionModal({
    Key? key,
    required this.categoryId,
    required this.categoryName,
    required this.repair,
    required this.statusOptions,
    required this.onStatusSelected,
  }) : super(key: key);

  @override
  State<StatusSelectionModal> createState() => _StatusSelectionModalState();
}

class _StatusSelectionModalState extends State<StatusSelectionModal> {
  bool _sendSms = true;

  Color _getCategoryColor() {
    switch (widget.categoryId) {
      case '2': // En cours
        return MacOSTheme.accentBlue;
      case '3': // En attente
        return MacOSTheme.accentPurple;
      case '4': // Terminé
        return MacOSTheme.successGreen;
      case '5': // Annulé
        return MacOSTheme.dangerRed;
      default:
        return MacOSTheme.accentBlue;
    }
  }

  IconData _getCategoryIcon() {
    switch (widget.categoryId) {
      case '2': return Icons.timelapse;
      case '3': return Icons.pause_circle_outline;
      case '4': return Icons.check_circle_outline;
      case '5': return Icons.cancel_outlined;
      default: return Icons.list;
    }
  }

  @override
  Widget build(BuildContext context) {
    final themeColor = _getCategoryColor();
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(16),
      child: Container(
        width: 450,
        constraints: const BoxConstraints(maxHeight: 800),
        decoration: BoxDecoration(
          color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.5),
              blurRadius: 40,
              offset: const Offset(0, 20),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: themeColor,
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(24),
                  topRight: Radius.circular(24),
                ),
                gradient: LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: [
                    themeColor,
                    themeColor.withOpacity(0.8),
                  ],
                ),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.swap_horiz, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(_getCategoryIcon(), color: Colors.white, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              "Statuts \"${widget.categoryName}\"",
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          "Sélectionnez le nouveau statut pour cette réparation",
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.9),
                            fontSize: 13,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // Body
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Column(
                  children: [
                    // Repair Info Card
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isDark ? Colors.black.withOpacity(0.2) : Colors.grey[100],
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: themeColor.withOpacity(0.3),
                        ),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                "Réparation #${widget.repair['id']}",
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: themeColor,
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.build, color: Colors.white, size: 16),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          _buildInfoRow("Client:", "${widget.repair['client_nom'] ?? ''} ${widget.repair['client_prenom'] ?? ''}"),
                          const SizedBox(height: 8),
                          _buildInfoRow("Appareil:", "${widget.repair['marque'] ?? ''} ${widget.repair['modele'] ?? ''}"),
                          const SizedBox(height: 8),
                          _buildInfoRow("Statut actuel:", widget.repair['statut'] ?? 'Inconnu'),
                        ],
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Status Buttons List
                    ...widget.statusOptions.map((status) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _buildStatusButton(status, themeColor, isDark),
                    )).toList(),

                    const SizedBox(height: 12),

                    // SMS Toggle
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: isDark ? const Color(0xFF141414) : const Color(0xFFF5F7FA),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.withOpacity(0.2),
                        ),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: MacOSTheme.accentBlue.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Icon(Icons.sms, color: MacOSTheme.accentBlue, size: 20),
                          ),
                          const SizedBox(width: 16),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  "Notification SMS",
                                  style: TextStyle(fontWeight: FontWeight.bold),
                                ),
                                SizedBox(height: 2),
                                Text(
                                  "Envoyer un SMS au client",
                                  style: TextStyle(fontSize: 12, color: Colors.grey),
                                ),
                              ],
                            ),
                          ),
                          CupertinoSwitch(
                            value: _sendSms,
                            activeColor: MacOSTheme.accentBlue,
                            onChanged: (v) => setState(() => _sendSms = v),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),

            // Footer
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close, size: 18),
                      label: const Text("Fermer"),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        foregroundColor: isDark ? Colors.white : Colors.black,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(color: Colors.grey)),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
      ],
    );
  }

  Widget _buildStatusButton(Map<String, dynamic> status, Color color, bool isDark) {
    return ElevatedButton(
      onPressed: () => widget.onStatusSelected(status['id'].toString(), _sendSms),
      style: ElevatedButton.styleFrom(
        backgroundColor: isDark ? const Color(0xFF2C2C2C) : Colors.white,
        foregroundColor: isDark ? Colors.white : Colors.black87,
        elevation: 0,
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(
            color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.withOpacity(0.3),
          ),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.check_circle, color: isDark ? Colors.white : Colors.black87, size: 20),
          const SizedBox(width: 12),
          Text(
            status['nom'] ?? 'Statut inconnu',
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
