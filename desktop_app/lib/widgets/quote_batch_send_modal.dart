import 'package:flutter/material.dart';
import '../services/api_service.dart';

class QuoteBatchSendModal extends StatefulWidget {
  final ApiService apiService;
  final int? shopId;

  const QuoteBatchSendModal({Key? key, required this.apiService, this.shopId}) : super(key: key);

  @override
  _QuoteBatchSendModalState createState() => _QuoteBatchSendModalState();
}

class _QuoteBatchSendModalState extends State<QuoteBatchSendModal> {
  bool _isLoading = true;
  bool _isSending = false;
  List<dynamic> _quotes = [];
  Set<int> _selectedIds = {};
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchPreview();
  }

  Future<void> _fetchPreview() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final response = await widget.apiService.batchSendDevis(action: 'preview', shopId: widget.shopId);
      final data = response['devis'] as List<dynamic>? ?? [];
      
      setState(() {
        _quotes = data;
        // Select all by default
        _selectedIds = data.map<int>((d) => int.parse(d['id'].toString())).toSet();
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _sendSelected() async {
    if (_selectedIds.isEmpty) return;

    setState(() => _isSending = true);

    try {
      final response = await widget.apiService.batchSendDevis(
        action: 'send',
        includeIds: _selectedIds.toList(),
        shopId: widget.shopId,
      );

      if (mounted) {
        Navigator.of(context).pop(true); // Return true to refresh
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(response['message'] ?? 'Envoi réussi'),
            backgroundColor: Colors.green,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Erreur: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      if (mounted) setState(() => _isSending = false);
    }
  }

  void _toggleAll(bool? value) {
    setState(() {
      if (value == true) {
        _selectedIds = _quotes.map<int>((d) => int.parse(d['id'].toString())).toSet();
      } else {
        _selectedIds.clear();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    return Dialog(
       shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
       child: Container(
         width: 600,
         height: 700,
         decoration: BoxDecoration(
           color: isDark ? const Color(0xFF1E1E1E) : Colors.white,
           borderRadius: BorderRadius.circular(16),
         ),
         child: Column(
           children: [
             // Header
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 border: Border(bottom: BorderSide(color: Theme.of(context).dividerColor)),
               ),
               child: Row(
                 children: [
                   const Icon(Icons.send_rounded, color: Colors.blue),
                   const SizedBox(width: 12),
                   const Text("Renvoyer les devis en attente", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                   const Spacer(),
                   IconButton(icon: const Icon(Icons.close), onPressed: () => Navigator.pop(context)),
                 ],
               ),
             ),

             // Content
             Expanded(
               child: _isLoading 
                 ? const Center(child: CircularProgressIndicator())
                 : _error != null
                   ? Center(child: Text("Erreur: $_error", style: const TextStyle(color: Colors.red)))
                   : _quotes.isEmpty
                     ? const Center(child: Text("Aucun devis en attente à renvoyer."))
                     : Column(
                         children: [
                           // Select All Bar
                           Container(
                             padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                             color: isDark ? Colors.black12 : Colors.grey.shade100,
                             child: Row(
                               children: [
                                 Checkbox(
                                   value: _selectedIds.length == _quotes.length && _quotes.isNotEmpty,
                                   onChanged: _toggleAll,
                                 ),
                                 Text("Tout sélectionner (${_quotes.length})"),
                                 const Spacer(),
                                 Text("${_selectedIds.length} sélectionné(s)", style: const TextStyle(fontWeight: FontWeight.bold)),
                               ],
                             ),
                           ),
                           
                           // List
                           Expanded(
                             child: ListView.separated(
                               itemCount: _quotes.length,
                               separatorBuilder: (c, i) => const Divider(height: 1),
                               itemBuilder: (context, index) {
                                 final quote = _quotes[index];
                                 final id = int.parse(quote['id'].toString());
                                 final isSelected = _selectedIds.contains(id);
                                 final clientName = "${quote['client_nom']} ${quote['client_prenom']}";
                                 final isExpired = quote['statut_relance']?.toString().contains('expire') ?? false;

                                 return ListTile(
                                   leading: Checkbox(
                                     value: isSelected,
                                     onChanged: (val) {
                                       setState(() {
                                         if (val == true) {
                                           _selectedIds.add(id);
                                         } else {
                                           _selectedIds.remove(id);
                                         }
                                       });
                                     },
                                   ),
                                   
                                   title: Row(
                                     children: [
                                       Text(quote['numero_devis'] ?? 'Devis #$id', style: const TextStyle(fontWeight: FontWeight.bold)),
                                       const SizedBox(width: 8),
                                       if (isExpired)
                                         Container(
                                           padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                           decoration: BoxDecoration(color: Colors.red.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                                           child: const Text("Expiré", style: TextStyle(color: Colors.red, fontSize: 10)),
                                         )
                                       else
                                         Container(
                                           padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                           decoration: BoxDecoration(color: Colors.orange.withOpacity(0.1), borderRadius: BorderRadius.circular(4)),
                                           child: const Text("En attente", style: TextStyle(color: Colors.orange, fontSize: 10)),
                                         )
                                     ],
                                   ),
                                   subtitle: Text("$clientName • ${quote['titre'] ?? 'Réparation'}"),
                                   trailing: Text(
                                     "${double.tryParse(quote['total_ttc'].toString())?.toStringAsFixed(2) ?? '0.00'} €",
                                     style: const TextStyle(fontWeight: FontWeight.bold),
                                   ),
                                   onTap: () {
                                      setState(() {
                                         if (isSelected) {
                                           _selectedIds.remove(id);
                                         } else {
                                           _selectedIds.add(id);
                                         }
                                       });
                                   },
                                 );
                               },
                             ),
                           ),
                         ],
                       ),
             ),
             
             // Footer
             Container(
               padding: const EdgeInsets.all(20),
               decoration: BoxDecoration(
                 border: Border(top: BorderSide(color: Theme.of(context).dividerColor)),
               ),
               child: Row(
                 mainAxisAlignment: MainAxisAlignment.end,
                 children: [
                   TextButton(
                     onPressed: () => Navigator.pop(context),
                     child: const Text("Annuler"),
                   ),
                   const SizedBox(width: 16),
                   ElevatedButton.icon(
                     onPressed: _isSending || _selectedIds.isEmpty ? null : _sendSelected,
                     style: ElevatedButton.styleFrom(
                       backgroundColor: Colors.blue,
                       foregroundColor: Colors.white,
                     ),
                     icon: _isSending ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.send),
                     label: Text("Envoyer (${_selectedIds.length})"),
                   ),
                 ],
               ),
             ),
           ],
         ),
       ),
    );
  }
}
