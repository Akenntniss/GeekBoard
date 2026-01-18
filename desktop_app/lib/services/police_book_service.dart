import 'dart:typed_data';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:intl/intl.dart';
import 'package:http/http.dart' as http;
import '../config/api_config.dart';

class PoliceBookService {
  static Future<Uint8List> generatePoliceBook({
    required List<Map<String, dynamic>> rachats,
    required int year,
    required Map<String, dynamic> shopInfo,
  }) async {
    final pdf = pw.Document();
    final dateFormat = DateFormat('dd/m/yyyy');
    
    // Charger les images (c'est long, il faudra peut-être le faire en batch ou gérer les erreurs)
    // Pour l'instant, on va essayer de charger les images à la volée ou utiliser des placeholders si échec
    
    // Page de garde / Entête globale
    // Le livre de police est un registre continu, mais ici on exporte l'année.
    
    // On divise en pages de tableau
    // Puis pages d'annexes
    
    // 1. Tableau principal (Paysage)
    pdf.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4.landscape,
        margin: const pw.EdgeInsets.all(20),
        header: (context) => _buildHeader(context, year, shopInfo),
        footer: (context) => _buildFooter(context, shopInfo),
        build: (context) {
          return [
            if (rachats.isEmpty)
              pw.Center(child: pw.Text("Aucun enregistrement pour l'année $year"))
            else
              pw.TableHelper.fromTextArray(
                border: pw.TableBorder.all(),
                headerStyle: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10),
                cellStyle: const pw.TextStyle(fontSize: 9),
                cellAlignment: pw.Alignment.centerLeft,
                headers: [
                  'N° Ordre',
                  'Date Achat',
                  'Description / N° Série',
                  'Identité Vendeur',
                  'Prix Achat',
                  'Règlement',
                  'Pièce d\'Identité',
                ],
                data: rachats.map((rachat) {
                  final desc = "${rachat['type_appareil'] ?? ''} ${rachat['modele'] ?? ''}" +
                      (rachat['sin'] != null && rachat['sin'].toString().isNotEmpty ? "\nS/N: ${rachat['sin']}" : "");
                  
                  final client = "${rachat['client_nom_seul'] ?? ''} ${rachat['client_prenom'] ?? ''}\n${rachat['telephone'] ?? ''}";
                  
                  final prix = rachat['prix_formatted'] ?? "${rachat['prix']} €";
                  
                  return [
                    rachat['id'].toString(),
                    rachat['date_formatted'] ?? '',
                    desc,
                    client,
                    prix,
                    'Espèces', // Par défaut comme le PHP
                    "Ref Dossier #${rachat['id']}",
                  ];
                }).toList(),
                columnWidths: {
                  0: const pw.FixedColumnWidth(50), // Id
                  1: const pw.FixedColumnWidth(70), // Date
                  2: const pw.FlexColumnWidth(2),   // Desc
                  3: const pw.FlexColumnWidth(1.5), // Client
                  4: const pw.FixedColumnWidth(70), // Prix
                  5: const pw.FixedColumnWidth(70), // Reglement
                  6: const pw.FixedColumnWidth(100),// Piece ID
                },
              ),
          ];
        },
      ),
    );

    // 2. Annexes (Une page par rachat avec photos)
    for (var rachat in rachats) {
      if (!_hasAnyPhoto(rachat)) continue;

      // Charger les images
      // Attention à la performance si beaucoup de rachats.
      // Idéalement on devrait le faire au fur et à mesure ou ne pas tout charger en mémoire.
      // pdf package construit tout en mémoire.
      // Pour une année complète (ex: 1000 rachats), ça va crasher.
      // LIMITATION: On ne peut peut-être pas tout faire d'un coup pour une grosse année.
      // Solution: Prévenir l'utilisateur ou paginer.
      // Pour l'instant on implémente pour un batch raisonnable.
      
      final photos = await _fetchPhotos(rachat);

      pdf.addPage(
        pw.Page(
          pageFormat: PdfPageFormat.a4.landscape,
          margin: const pw.EdgeInsets.all(20),
          build: (context) {
            return pw.Column(
              crossAxisAlignment: pw.CrossAxisAlignment.start,
              children: [
                pw.Header(
                  level: 1,
                  child: pw.Row(
                    mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                    children: [
                      pw.Text("Dossier #${rachat['id']} - ${rachat['client_nom']}", style: pw.TextStyle(fontSize: 18, fontWeight: pw.FontWeight.bold)),
                      pw.Text(rachat['date_formatted'] ?? '', style: const pw.TextStyle(fontSize: 14)),
                    ],
                  ),
                ),
                pw.SizedBox(height: 10),
                pw.Text("Annexe au Livre de Police", style: const pw.TextStyle(fontSize: 12, color: PdfColors.grey700)),
                pw.Divider(),
                pw.SizedBox(height: 20),
                
                // Grille de photos
                pw.Expanded(
                  child: pw.GridView(
                    crossAxisCount: 2,
                    childAspectRatio: 1.5,
                    crossAxisSpacing: 20,
                    mainAxisSpacing: 20,
                    children: [
                      _buildPhotoFrame('Pièce d\'Identité', photos['identite']),
                      _buildPhotoFrame('Photo du Client', photos['client']),
                      _buildPhotoFrame('Photo de l\'Appareil', photos['appareil']),
                      _buildPhotoFrame('Signature', photos['signature']),
                    ],
                  ),
                ),
              ],
            );
          },
        ),
      );
    }

    return pdf.save();
  }

  static pw.Widget _buildHeader(pw.Context context, int year, Map<String, dynamic> shopInfo) {
    return pw.Column(
      children: [
        pw.Text('REGISTRE DES OBJETS MOBILIERS (LIVRE DE POLICE)', style: pw.TextStyle(fontSize: 20, fontWeight: pw.FontWeight.bold)),
        pw.Text("Année $year - ${shopInfo['name'] ?? 'GEEKBOARD'}", style: const pw.TextStyle(fontSize: 14)),
        pw.Divider(),
        pw.SizedBox(height: 10),
      ],
    );
  }

  static pw.Widget _buildFooter(pw.Context context, Map<String, dynamic> shopInfo) {
    return pw.Container(
      alignment: pw.Alignment.center,
      margin: const pw.EdgeInsets.only(top: 10),
      child: pw.Text(
        "${shopInfo['name'] ?? 'GEEKBOARD'} - Page ${context.pageNumber} / ${context.pagesCount}",
        style: const pw.TextStyle(fontSize: 10, color: PdfColors.grey),
      ),
    );
  }

  static pw.Widget _buildPhotoFrame(String label, pw.MemoryImage? image) {
    return pw.Container(
      decoration: pw.BoxDecoration(
        border: pw.Border.all(color: PdfColors.grey300),
      ),
      child: pw.Column(
        children: [
          pw.Container(
            color: PdfColors.grey200,
            width: double.infinity,
            padding: const pw.EdgeInsets.all(4),
            child: pw.Text(label, textAlign: pw.TextAlign.center, style: pw.TextStyle(fontWeight: pw.FontWeight.bold)),
          ),
          pw.Expanded(
            child: image != null
                ? pw.Center(child: pw.Image(image, fit: pw.BoxFit.contain))
                : pw.Center(child: pw.Text("Non disponible", style: const pw.TextStyle(color: PdfColors.grey))),
          ),
        ],
      ),
    );
  }

  static bool _hasAnyPhoto(Map<String, dynamic> rachat) {
    return (rachat['photo_identite'] != null && rachat['photo_identite'].toString().isNotEmpty) ||
           (rachat['client_photo'] != null && rachat['client_photo'].toString().isNotEmpty) ||
           (rachat['photo_appareil'] != null && rachat['photo_appareil'].toString().isNotEmpty) ||
           (rachat['signature'] != null && rachat['signature'].toString().isNotEmpty);
  }

  static Future<Map<String, pw.MemoryImage?>> _fetchPhotos(Map<String, dynamic> rachat) async {
    final Map<String, pw.MemoryImage?> result = {
      'identite': null,
      'client': null,
      'appareil': null,
      'signature': null,
    };

    // Helper pour charger une image
    // Les URLs d'images sont relatives dans la DB : ex "myphoto.jpg"
    // L'URL complète est ApiConfig.siteUrl + '/assets/images/rachat/' + filename
    
    Future<pw.MemoryImage?> load(String? filename) async {
      if (filename == null || filename.isEmpty) return null;
      try {
        final url = '${ApiConfig.siteUrl}/assets/images/rachat/$filename';
        final response = await http.get(Uri.parse(url)).timeout(const Duration(seconds: 5));
        if (response.statusCode == 200) {
          return pw.MemoryImage(response.bodyBytes);
        }
      } catch (e) {
        print('Erreur chargement image $filename: $e');
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
