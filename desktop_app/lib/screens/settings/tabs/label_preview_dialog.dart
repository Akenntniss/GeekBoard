import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class LabelPreviewDialog extends StatelessWidget {
  final String layoutId;
  final String layoutName;

  const LabelPreviewDialog({
    super.key, 
    required this.layoutId,
    required this.layoutName,
  });

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 500,
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text("Prévisualisation : $layoutName", style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                IconButton(
                  icon: const Icon(Icons.close),
                  onPressed: () => Navigator.of(context).pop(),
                ),
              ],
            ),
            const SizedBox(height: 24),
            Center(
              child: Container(
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey[300]!, width: 2),
                  color: Colors.white,
                  boxShadow: const [BoxShadow(blurRadius: 10, color: Colors.black12)],
                ),
                child: _buildPreviewContent(context),
              ),
            ),
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text("Fermer"),
                ),
                const SizedBox(width: 16),
                ElevatedButton.icon(
                  onPressed: () => Navigator.of(context).pop(true), // Return true to confirm
                  icon: const Icon(Icons.check),
                  label: const Text("Choisir ce format"),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPreviewContent(BuildContext context) {
    // Mock Data
    final data = {
      'id': 'REP-2026-1042',
      'client': 'Jean Dupont',
      'device': 'iPhone 13 Pro',
      'problem': 'Écran cassé + Batterie',
      'date': DateFormat('dd/MM/yyyy HH:mm').format(DateTime.now()),
      'shop': 'Magasin Demo',
    };

    switch (layoutId) {
      case '4x6_moderne':
        return _build4x6Moderne(context, data);
      case '4x6_business':
        return _build4x6Business(context, data);
      case 'mini_qr':
        return _buildMiniQr(context, data);
      case 'a4_moderne':
      case 'a4_split':
         return _buildA4Placeholder(context, data, layoutId);
      default:
        return _build4x6Moderne(context, data);
    }
  }

  // --- Layouts ---

  Widget _build4x6Moderne(BuildContext context, Map<String, String> data) {
    return Container(
      width: 300,
      height: 450, // Approx 4x6 ratio
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(data['shop']!, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.black)),
              Text(data['date']!, style: const TextStyle(fontSize: 12, color: Colors.grey)),
            ],
          ),
          const Divider(thickness: 2, color: Colors.black),
          const SizedBox(height: 12),
          
          // ID
          Center(child: Text(data['id']!, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.black))),
          const SizedBox(height: 20),
          
          // Client
          Container(
            padding: const EdgeInsets.all(8),
            color: Colors.grey[100],
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text("CLIENT", style: TextStyle(fontSize: 10, color: Colors.grey)),
                Text(data['client']!, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.black)),
              ],
            ),
          ),
          const SizedBox(height: 12),
          
          // Device
           Container(
            padding: const EdgeInsets.all(8),
            color: Colors.grey[100],
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text("APPAREIL", style: TextStyle(fontSize: 10, color: Colors.grey)),
                Text(data['device']!, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black)),
                Text(data['problem']!, style: const TextStyle(fontSize: 14, color: Colors.black87)),
              ],
            ),
          ),
          
          const Spacer(),
          
          // QR Code Area
          Center(
            child: Container(
              width: 100,
              height: 100,
              color: Colors.black,
              alignment: Alignment.center,
              child: const Icon(Icons.qr_code_2, color: Colors.white, size: 80),
            ),
          ),
          const SizedBox(height: 8),
          const Center(child: Text("Scannez pour le suivi", style: TextStyle(fontSize: 10, color: Colors.black))),
        ],
      ),
    );
  }

  Widget _build4x6Business(BuildContext context, Map<String, String> data) {
    return Container(
      width: 300,
      height: 450,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(border: Border.all(color: Colors.black, width: 4)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Text(data['shop']!.toUpperCase(), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, letterSpacing: 2, color: Colors.black)),
          const SizedBox(height: 4),
          Container(height: 2, width: 50, color: Colors.black),
          const SizedBox(height: 30),
          
          Text(data['id']!, style: const TextStyle(fontSize: 28, fontFamily: 'Courier', fontWeight: FontWeight.bold, color: Colors.black)),
          const SizedBox(height: 30),
          
          const Text("CLIENT", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black)),
          Text(data['client']!, style: const TextStyle(fontSize: 22, color: Colors.black)),
          
          const SizedBox(height: 20),
          
          const Text("SERVICE", style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black)),
          Text(data['device']!, style: const TextStyle(fontSize: 18, color: Colors.black)),
          
          const Spacer(),
          
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(border: Border.all(color: Colors.black)),
            child: const Icon(Icons.qr_code, size: 60, color: Colors.black),
          )
        ],
      ),
    );
  }

  Widget _buildMiniQr(BuildContext context, Map<String, String> data) {
    return Container(
      width: 200,
      height: 200,
      padding: const EdgeInsets.all(12),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Expanded(
            child: Container(
              color: Colors.black,
              alignment: Alignment.center,
              child: const Icon(Icons.qr_code_2, color: Colors.white, size: 100),
            ),
          ),
          const SizedBox(height: 8),
          Text(data['id']!, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.black)),
        ],
      ),
    );
  }
  
  Widget _buildA4Placeholder(BuildContext context, Map<String, String> data, String type) {
     return Container(
      width: 300,
      height: 424, // A4 ratio approx
      color: Colors.white,
      child: Stack(
        children: [
          Positioned.fill(
            child: Column(
              children: [
                 Container(height: 20, color: Colors.blue[800]),
                 Expanded(child: Center(child: Text("Format A4\n$type", textAlign: TextAlign.center, style: TextStyle(color: Colors.grey[300], fontSize: 32, fontWeight: FontWeight.bold)))),
                 Container(height: 20, color: Colors.blue[800]),
              ],
            )
          ),
          Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(children: [
                   Container(width: 50, height: 50, color: Colors.blue),
                   const SizedBox(width: 16),
                   Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(data['shop']!, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.black)),
                      Text("Devis / Ordre de réparation", style: const TextStyle(color: Colors.black54)),
                   ])
                ]),
                const SizedBox(height: 40),
                Text("Client: ${data['client']}", style: const TextStyle(fontSize: 18, color: Colors.black)),
                Text("Appareil: ${data['device']}", style: const TextStyle(fontSize: 16, color: Colors.black)),
                const SizedBox(height: 20),
                Container(
                  padding: const EdgeInsets.all(16),
                  color: Colors.grey[100],
                  child: const Text("Détails complets de la réparation, conditions générales, signature...", style: TextStyle(color: Colors.grey)),
                )
              ],
            ),
          )
        ],
      ),
     );
  }
}
