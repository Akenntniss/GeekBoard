import 'dart:typed_data';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:http/http.dart' as http;
import '../config/api_config.dart';

class RachatPdfService {
  static Future<Uint8List> generateCertificate({
    required Map<String, dynamic> rachat,
    required Map<String, dynamic> shopInfo,
  }) async {
    final pdf = pw.Document();
    
    // Charger les images
    final photos = await _fetchPhotos(rachat);
    
    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(30),
        build: (context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              // Header
              _buildHeader(shopInfo, rachat),
              pw.SizedBox(height: 20),
              
              // Infos Vendeur & Appareil
              pw.Row(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Expanded(child: _buildVendorSection(rachat)),
                  pw.SizedBox(width: 20),
                  pw.Expanded(child: _buildDeviceSection(rachat)),
                ],
              ),
              
              pw.SizedBox(height: 20),
              
              // Transaction
              _buildTransactionSection(rachat),
              
              pw.SizedBox(height: 20),
              
              // Déclaration
              _buildDeclarationSection(rachat),
              
              pw.SizedBox(height: 20),
              
              // Signatures
              _buildSignaturesSection(photos['signature']),
              
              pw.Spacer(),
              
              // Footer Photos
              pw.Text("Photos jointes au dossier :", style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10)),
              pw.SizedBox(height: 5),
              pw.Expanded(
                flex: 3,
                child: pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                    if (photos['identite'] != null) _buildPhotoThumb("Identité", photos['identite']!),
                    if (photos['appareil'] != null) _buildPhotoThumb("Appareil", photos['appareil']!),
                    if (photos['client'] != null) _buildPhotoThumb("Client", photos['client']!),
                  ],
                ),
              ),
              
              pw.SizedBox(height: 10),
              pw.Center(child: pw.Text("Document généré le " + DateTime.now().toString().split('.')[0], style: const pw.TextStyle(color: PdfColors.grey, fontSize: 8))),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  static pw.Widget _buildHeader(Map<String, dynamic> shopInfo, Map<String, dynamic> rachat) {
    return pw.Column(
      children: [
        pw.Text((shopInfo['name'] ?? 'MAGASIN').toUpperCase(), style: pw.TextStyle(fontSize: 20, fontWeight: pw.FontWeight.bold)),
        pw.SizedBox(height: 5),
        pw.Text("ATTESTATION DE RACHAT / CESSION", style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold, color: PdfColors.blueGrey800)),
        pw.Divider(),
        pw.Row(
          mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
          children: [
            pw.Text("Dossier N°: ${rachat['id']}", style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
            pw.Text("Date: ${rachat['date_formatted'] ?? ''}", style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
          ],
        )
      ],
    );
  }

  static pw.Widget _buildVendorSection(Map<String, dynamic> rachat) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(10),
      decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey400), borderRadius: pw.BorderRadius.circular(4)),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text("VENDEUR (CLIENT)", style: pw.TextStyle(fontWeight: pw.FontWeight.bold, decoration: pw.TextDecoration.underline)),
          pw.SizedBox(height: 10),
          pw.Text("Nom Prénom : ${rachat['client_prenom']} ${rachat['client_nom']}"),
          pw.Text("Téléphone : ${rachat['telephone'] ?? '-'}"),
          pw.Text("Email : ${rachat['email'] ?? '-'}"),
        ],
      ),
    );
  }

  static pw.Widget _buildDeviceSection(Map<String, dynamic> rachat) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(10),
      decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey400), borderRadius: pw.BorderRadius.circular(4)),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text("APPAREIL / OBJET", style: pw.TextStyle(fontWeight: pw.FontWeight.bold, decoration: pw.TextDecoration.underline)),
          pw.SizedBox(height: 10),
          pw.Text("Type : ${rachat['type_appareil'] ?? '-'}"),
          pw.Text("Modèle : ${rachat['modele'] ?? '-'}"),
          pw.Text("N° Série / IMEI : ${rachat['sin'] ?? '-'}"),
        ],
      ),
    );
  }

  static pw.Widget _buildTransactionSection(Map<String, dynamic> rachat) {
    return pw.Container(
      padding: const pw.EdgeInsets.all(10),
      color: PdfColors.grey100,
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text("PRIX DE RACHAT CONVENU :", style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
          pw.Text(rachat['prix_formatted'] ?? "${rachat['prix']} €", style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold)),
        ],
      ),
    );
  }

  static pw.Widget _buildDeclarationSection(Map<String, dynamic> rachat) {
    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Text("DÉCLARATION SUR L'HONNEUR :", style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold)),
        pw.SizedBox(height: 5),
        pw.Text(
          "Je soussigné(e) ${rachat['client_prenom']} ${rachat['client_nom']}, certifie sur l'honneur être le propriétaire légitime de l'appareil décrit ci-dessus et avoir la pleine capacité juridique pour le vendre. Je certifie que cet objet n'est ni gagé, ni volé.\nJe cède la propriété de cet appareil au magasin en échange de la somme convenue.",
          style: const pw.TextStyle(fontSize: 9, fontStyle: pw.FontStyle.italic),
          textAlign: pw.TextAlign.justify
        ),
      ],
    );
  }

  static pw.Widget _buildSignaturesSection(pw.MemoryImage? signature) {
    return pw.Row(
      mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Column(
          children: [
            pw.Text("Cachet du Magasin", style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold)),
            pw.SizedBox(height: 40),
            pw.Container(
              width: 100, 
              height: 50, 
              decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey300)),
              child: pw.Center(child: pw.Text("Tampon", style: const pw.TextStyle(color: PdfColors.grey300)))
            ),
          ],
        ),
        pw.Column(
          children: [
            pw.Text("Signature du Vendeur", style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold)),
            pw.Text("(Précédée de la mention 'Lu et approuvé')", style: const pw.TextStyle(fontSize: 8)),
            pw.SizedBox(height: 5),
            pw.Container(
              width: 120,
              height: 60,
              decoration: pw.BoxDecoration(
                border: pw.Border.all(color: PdfColors.grey300),
                color: PdfColors.grey50
              ),
              child: signature != null 
                  ? pw.Image(signature, fit: pw.BoxFit.contain)
                  : pw.Center(child: pw.Text("Signé électroniquement", style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey))),
            ),
            pw.Text("Document signé numériquement", style: const pw.TextStyle(fontSize: 6, color: PdfColors.grey)),
          ],
        ),
      ],
    );
  }

  static pw.Widget _buildPhotoThumb(String label, pw.MemoryImage image) {
    return pw.Container(
      width: 150,
      height: 100,
      decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey300)),
      child: pw.Column(
        children: [
          pw.Container(
            width: double.infinity, 
            color: PdfColors.grey200, 
            padding: const pw.EdgeInsets.all(2),
            child: pw.Text(label, textAlign: pw.TextAlign.center, style: const pw.TextStyle(fontSize: 8))
          ),
          pw.Expanded(child: pw.Image(image, fit: pw.BoxFit.contain)),
        ],
      ),
    );
  }

  static Future<Map<String, pw.MemoryImage?>> _fetchPhotos(Map<String, dynamic> rachat) async {
    final Map<String, pw.MemoryImage?> result = {
      'identite': null,
      'client': null,
      'appareil': null,
      'signature': null,
    };

    Future<pw.MemoryImage?> load(String? filename) async {
      if (filename == null || filename.isEmpty) return null;
      try {
        final url = '${ApiConfig.siteUrl}/assets/images/rachat/$filename';
        final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 5));
        if (response.statusCode == 200) {
          return pw.MemoryImage(response.bodyBytes);
        }
      } catch (e) {
        print('Erreur chargement image PDF $filename: $e');
      }
      return null;
    }

    result['identite'] = await load(rachat['photo_identite']);
    result['client'] = await load(rachat['client_photo']);
    result['appareil'] = await load(rachat['photo_appareil']);
    result['signature'] = await load(rachat['signature']);

    return result;
  }
}
