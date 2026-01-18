import 'package:flutter/material.dart';

class MissionCard extends StatelessWidget {
  final Map<String, dynamic> mission;
  final String status; // 'in_progress', 'available', 'completed'
  final VoidCallback? onAction;

  const MissionCard({
    super.key,
    required this.mission,
    required this.status,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.2 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header with Gradient Border Top
          Container(
            height: 4,
            decoration: const BoxDecoration(
              borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
              gradient: LinearGradient(
                colors: [Color(0xFF3b82f6), Color(0xFF06b6d4)],
              ),
            ),
          ),
          
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Top Row: Type Badge & Reward
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.blue.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.blue.withOpacity(0.3)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.rocket_launch, size: 12, color: Colors.blue[300]),
                          const SizedBox(width: 6),
                          Text(
                            mission['type_nom'] ?? 'Mission',
                            style: TextStyle(color: Colors.blue[300], fontSize: 11, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                    Text(
                      '${mission['recompense_euros_formatted']}',
                      style: const TextStyle(
                        color: Color(0xFF10B981),
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ],
                ),
                
                const SizedBox(height: 16),
                
                // Title
                Text(
                  mission['titre'] ?? 'Une Mission',
                  style: TextStyle(
                    color: isDark ? Colors.white : Colors.black87,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                
                const SizedBox(height: 8),
                
                // Description
                Text(
                  mission['description'] ?? '',
                  style: TextStyle(color: isDark ? Colors.grey[400] : Colors.grey[600], fontSize: 13, height: 1.5),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
                
                const SizedBox(height: 20),
                
                // Specific Content based on status
                if (status == 'in_progress') _buildProgressBar(),
                if (status == 'available') _buildAvailableDetails(),
                if (status == 'completed') _buildCompletedDetails(),
              ],
            ),
          ),
          
          // Action Button Footer (if visible)
          if (status != 'completed')
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
              child: ElevatedButton(
                onPressed: onAction,
                style: ElevatedButton.styleFrom(
                  backgroundColor: status == 'available' 
                      ? const Color(0xFF3b82f6) 
                      : const Color(0xFF8B5CF6),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                ),
                child: Text(
                  status == 'available' ? '🔥 Rejoindre la mission' : '✅ Valider une tâche',
                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildProgressBar() {
    // Note: Since MissionCard is a StatelessWidget, we can't easily access context here without passing it or using a Builder.
    // Ideally we should refactor to pass context, but since this is an internal widget method, we can change signature to accept context 
    // OR just use Builder inside or rely on inherited widget if possible.
    // However, since this method is called within build(), we can just use a boolean passed/derived earlier, BUT this method is separated.
    // Let's modify it to use Builder or pass isDark. 
    // Simplest fix: Add context parameter or assume access because it's part of class? No it's a method on class.
    // Wait, build() has context. I can't access it here easily unless I pass it.
    // Actually, I can just use Builder or use a helper function that takes context.
    
    // Better idea: Since I'm editing the file, I'll update the call sites to pass context or 'isDark'.
    // But easier: wrap the return in Builder if needed, OR just grab context from somewhere else? No.
    // I will simply modify the calls to _buildProgressBar to _buildProgressBar(context).
    
    // Actually, let's look at call site: line 107: if (status == 'in_progress') _buildProgressBar(),
    // I need to change that line too then.
    // Let's just assume I can pass context.
    
    // WAIT, actually I will inject context into this method.
    return Builder(builder: (context) {
       final isDark = Theme.of(context).brightness == Brightness.dark;
       final int progress = mission['progression'] ?? 0;
       final int target = mission['objectif_nombre'] ?? 10;
       final int percentage = mission['percentage'] ?? 0;

       return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Progression',
              style: TextStyle(color: Colors.grey[500], fontSize: 12),
            ),
            Text(
              '$progress / $target',
              style: TextStyle(color: isDark ? Colors.white : Colors.black87, fontWeight: FontWeight.bold, fontSize: 12),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Container(
          height: 8,
          decoration: BoxDecoration(
            color: isDark ? Colors.white.withOpacity(0.1) : Colors.grey.shade200,
            borderRadius: BorderRadius.circular(4),
          ),
          child: FractionallySizedBox(
            widthFactor: percentage / 100,
            child: Container(
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Colors.blue, Colors.purple]),
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          ),
        ),
      ],
    );
    });
  }

  Widget _buildAvailableDetails() {
    return Row(
      children: [
        Icon(Icons.people, size: 14, color: Colors.grey[500]),
        const SizedBox(width: 6),
        Text(
          '${mission['nb_participants'] ?? 0} participants',
          style: TextStyle(color: Colors.grey[500], fontSize: 12),
        ),
        const Spacer(),
        Icon(Icons.calendar_today, size: 14, color: Colors.grey[500]),
        const SizedBox(width: 6),
        Text(
          'Fin le ${mission['date_fin'] ?? '...' }',
          style: TextStyle(color: Colors.grey[500], fontSize: 12),
        ),
      ],
    );
  }

  Widget _buildCompletedDetails() {
     return Container(
       padding: const EdgeInsets.all(12),
       decoration: BoxDecoration(
         color: const Color(0xFF10B981).withOpacity(0.1),
         borderRadius: BorderRadius.circular(8),
         border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
       ),
       child: const Row(
         mainAxisAlignment: MainAxisAlignment.center,
         children: [
           Icon(Icons.check_circle, color: Color(0xFF10B981), size: 16),
           SizedBox(width: 8),
           Text(
             'Mission validée & payée',
             style: TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 13),
           ),
         ],
       ),
     );
  }
}
