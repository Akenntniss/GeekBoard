import 'package:flutter/material.dart';
import 'package:geekboard_desktop/widgets/user_avatar.dart';

class LogCard extends StatelessWidget {
  final Map<String, dynamic> log;
  final VoidCallback? onTap;

  const LogCard({
    super.key,
    required this.log,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isReparation = log['log_source'] == 'reparation';
    final Color mainColor = isReparation ? const Color(0xFF10B981) : const Color(0xFF06B6D4);
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade200;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: borderColor),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.1 : 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      clipBehavior: Clip.hardEdge,
      child: Stack(
        children: [
          Positioned(
            left: 0,
            top: 0,
            bottom: 0,
            width: 4,
            child: Container(color: mainColor),
          ),
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: onTap,
              child: Padding(
                padding: const EdgeInsets.only(left: 20, right: 16, top: 16, bottom: 16), // Adjusted padding for left bar
                child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Icon
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: mainColor.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    isReparation ? Icons.build : Icons.task,
                    color: mainColor,
                    size: 20,
                  ),
                ),
                const SizedBox(width: 16),
                
                // Content
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFF3B82F6).withOpacity(0.2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              log['action_label'] ?? 'Action',
                              style: const TextStyle(
                                color: Color(0xFF60A5FA),
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          Text(
                            log['formatted_date'] ?? '',
                            style: TextStyle(color: Colors.grey[500], fontSize: 12),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      
                      Text(
                        log['reference_title'] ?? 'Référence inconnue',
                        style: TextStyle(
                          color: isDark ? Colors.white : Colors.black87,
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                        ),
                      ),
                      
                      const SizedBox(height: 4),
                      
                      RichText(
                        text: TextSpan(
                          style: TextStyle(color: Colors.grey[400], fontSize: 13),
                          children: [
                            const TextSpan(text: 'Par '),
                            TextSpan(
                              text: log['employe_nom'] ?? 'Inconnu',
                              style: const TextStyle(color: Color(0xFF94A3B8), fontWeight: FontWeight.w600),
                            ),
                          ],
                        ),
                      ),
                      
                      if (log['details'] != null && log['details'].toString().isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.info_outline, size: 14, color: Colors.grey[500]),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  log['details'],
                                  style: TextStyle(color: isDark ? Colors.grey[300] : Colors.grey[700], fontSize: 13, fontStyle: FontStyle.italic),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                      
                      if (log['statut_avant'] != null && log['statut_apres'] != null) ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            _buildStatusBadge(log['statut_avant'], isDark),
                            const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 8),
                              child: Icon(Icons.arrow_forward, size: 14, color: Colors.grey),
                            ),
                            _buildStatusBadge(log['statut_apres'], isDark),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(String? status, bool isDark) {
    if (status == null) return const SizedBox.shrink();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(4),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade300),
      ),
      child: Text(
        status.replaceAll('_', ' '),
        style: TextStyle(color: Colors.grey[isDark ? 400 : 700], fontSize: 11),
      ),
    );
  }
}
