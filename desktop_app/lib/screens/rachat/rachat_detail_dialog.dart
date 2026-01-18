import 'package:flutter/material.dart';
import 'package:printing/printing.dart';
import 'package:provider/provider.dart';
import '../../services/auth_service.dart';
import '../../services/rachat_pdf_service.dart';
import '../../config/api_config.dart';

class RachatDetailDialog extends StatelessWidget {
  final Map<String, dynamic> rachat;

  const RachatDetailDialog({super.key, required this.rachat});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final textColor = isDark ? Colors.white : Colors.black;

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        width: 800,
        height: 700,
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text("Détails du Rachat", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: textColor)),
                IconButton(onPressed: () => Navigator.of(context).pop(), icon: const Icon(Icons.close)),
              ],
            ),
            const Divider(),
            
            // Content
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildSectionTitle("Informations Client", textColor),
                    _buildInfoRow("Nom", "${rachat['client_prenom']} ${rachat['client_nom']}", textColor),
                    _buildInfoRow("Téléphone", rachat['telephone'] ?? '-', textColor),
                    _buildInfoRow("Email", rachat['email'] ?? '-', textColor),
                    
                    const SizedBox(height: 20),
                    _buildSectionTitle("Informations Appareil", textColor),
                    _buildInfoRow("Modèle", rachat['modele'] ?? '-', textColor),
                    _buildInfoRow("IMEI / SIN", rachat['sin'] ?? '-', textColor),
                    _buildInfoRow("Prix d'achat", rachat['prix_formatted'] ?? '-', textColor),
                    _buildInfoRow("Date", rachat['date_formatted'] ?? '-', textColor),
                    
                    const SizedBox(height: 20),
                    _buildSectionTitle("Photos & Signature", textColor),
                    const SizedBox(height: 10),
                    GridView.count(
                      crossAxisCount: 2,
                      crossAxisSpacing: 16,
                      mainAxisSpacing: 16,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      children: [
                        _buildImageCard("Photo Identité", rachat['photo_identite']),
                        _buildImageCard("Photo Appareil", rachat['photo_appareil']),
                        _buildImageCard("Photo Client", rachat['client_photo']),
                        _buildImageCard("Signature", rachat['signature'], fit: BoxFit.contain), // Signature often needs contain
                      ],
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 20),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text("Fermer"),
                ),
                const SizedBox(width: 8),
                ElevatedButton.icon(
                  onPressed: () => _printAttestation(context, rachat),
                  icon: const Icon(Icons.print), 
                  label: const Text("Imprimer")
                )
              ],
            )
          ],
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Text(title, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: color, decoration: TextDecoration.underline)),
    );
  }

  Widget _buildInfoRow(String label, String value, Color color) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: [
          SizedBox(width: 120, child: Text("$label :", style: TextStyle(fontWeight: FontWeight.bold, color: color.withOpacity(0.7)))),
          Expanded(child: Text(value, style: TextStyle(color: color, fontSize: 16))),
        ],
      ),
    );
  }

  Widget _buildImageCard(String label, String? filename, {BoxFit fit = BoxFit.cover}) {
    if (filename == null || filename.isEmpty) {
      return Card(child: Center(child: Text("$label non disponible")));
    }
    
    // Construct URL
    final url = "${ApiConfig.siteUrl}/assets/images/rachat/$filename";
    
    return Card(
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            color: Colors.black12,
            child: Text(label, style: const TextStyle(fontWeight: FontWeight.bold), textAlign: TextAlign.center),
          ),
          Expanded(
            child: Image.network(
              url, 
              fit: fit,
              errorBuilder: (ctx, err, stack) => const Center(child: Icon(Icons.broken_image, size: 40, color: Colors.grey)),
              loadingBuilder: (ctx, child, progress) {
                if (progress == null) return child;
                return const Center(child: CircularProgressIndicator());
              },
            ),
          ),
        ],
      ),
    );
  }
  Future<void> _printAttestation(BuildContext context, Map<String, dynamic> rachat) async {
    try {
      final shopName = context.read<AuthService>().currentShop?.name ?? 'GEEKBOARD';
      final shopInfo = {'name': shopName}; // Add more info if available
      
      final pdfBytes = await RachatPdfService.generateCertificate(
        rachat: rachat,
        shopInfo: shopInfo,
      );
      
      await Printing.layoutPdf(
        onLayout: (format) => pdfBytes,
        name: 'Attestation_Rachat_${rachat['id']}.pdf',
      );
    } catch (e) {
      if (context.mounted) {
         ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Erreur impression: $e'), backgroundColor: Colors.red));
      }
    }
  }
}
