import 'package:flutter/material.dart';

class BugCard extends StatelessWidget {
  final Map<String, dynamic> bug;
  final Function(String) onStatusChange;
  final VoidCallback onDelete;

  const BugCard({
    super.key,
    required this.bug,
    required this.onStatusChange,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = bug['status'] ?? 'nouveau';
    final color = _getStatusColor(status);
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    final cardColor = isDark ? const Color(0xFF1E293B) : Colors.white;
    final borderColor = isDark ? Colors.white.withOpacity(0.05) : Colors.grey.shade200;
    final textColor = isDark ? Colors.white : Colors.black87;

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
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(width: 4, color: color),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.black.withOpacity(0.3),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              '#${bug['id']}',
                              style: const TextStyle(
                                color: Colors.white70,
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ),
                          Text(
                            bug['formatted_date'] ?? '',
                            style: TextStyle(color: Colors.grey[500], fontSize: 12),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        bug['description'] ?? 'Pas de description',
                        style: TextStyle(color: textColor, fontSize: 14),
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (bug['page_clean'] != null && bug['page_clean'].isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Icon(Icons.link, size: 12, color: Colors.blue[400]),
                            const SizedBox(width: 4),
                            Text(
                              bug['page_clean'],
                              style: TextStyle(color: Colors.blue[400], fontSize: 12),
                            ),
                          ],
                        ),
                      ],
                      const SizedBox(height: 16),
                      Divider(color: isDark ? Colors.white10 : Colors.grey.shade200),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          _buildStatusBadge(status, color),
                          Row(
                            children: [
                              if (status != 'resolu')
                                IconButton(
                                  icon: const Icon(Icons.check_circle_outline, color: Colors.green),
                                  tooltip: 'Marquer résolu',
                                  onPressed: () => onStatusChange('resolu'),
                                ),
                              PopupMenuButton<String>(
                                icon: const Icon(Icons.more_vert, color: Colors.grey),
                                color: isDark ? const Color(0xFF0F172A) : Colors.white,
                                onSelected: (val) {
                                  if (val == 'delete') {
                                    onDelete();
                                  } else {
                                    onStatusChange(val);
                                  }
                                },
                                itemBuilder: (context) => [
                                  _buildPopupItem('nouveau', 'Nouveau', Colors.pink, isDark),
                                  _buildPopupItem('en_cours', 'En cours', Colors.cyan, isDark),
                                  _buildPopupItem('invalide', 'Invalide', Colors.orange, isDark),
                                  const PopupMenuDivider(),
                                  const PopupMenuItem(
                                    value: 'delete',
                                    child: Row(children: [Icon(Icons.delete, size: 16, color: Colors.red), SizedBox(width: 8), Text('Supprimer', style: TextStyle(color: Colors.red))]),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  PopupMenuItem<String> _buildPopupItem(String value, String label, Color color, bool isDark) {
    return PopupMenuItem(
      value: value,
      child: Row(
        children: [
          Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
          const SizedBox(width: 8),
          Text(label, style: TextStyle(color: isDark ? Colors.white : Colors.black87)),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(String status, Color color) {
    String label = status;
    switch(status) {
      case 'nouveau': label = 'NOUVEAU'; break;
      case 'en_cours': label = 'EN COURS'; break;
      case 'resolu': label = 'RÉSOLU'; break;
      case 'invalide': label = 'INVALIDE'; break;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Text(
        label,
        style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold),
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch(status) {
      case 'nouveau': return Colors.pinkAccent;
      case 'en_cours': return Colors.cyanAccent;
      case 'resolu': return Colors.greenAccent;
      case 'invalide': return Colors.orangeAccent;
      default: return Colors.grey;
    }
  }
}
