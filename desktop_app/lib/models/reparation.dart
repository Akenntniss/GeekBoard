/// Modèle Reparation - Représente une réparation

class Reparation {
  final int id;
  final String numero;
  final String appareil;
  final String? marque;
  final String? modele;
  final String? probleme;
  final String status;
  final double? prix;
  final DateTime? dateCreation;
  final DateTime? dateModification;
  final DateTime? dateLivraison;
  final int? clientId;
  final String? clientNom;
  final String? clientPrenom;
  final String? clientTelephone;
  final String? clientEmail;

  Reparation({
    required this.id,
    required this.numero,
    required this.appareil,
    this.marque,
    this.modele,
    this.probleme,
    required this.status,
    this.prix,
    this.dateCreation,
    this.dateModification,
    this.dateLivraison,
    this.clientId,
    this.clientNom,
    this.clientPrenom,
    this.clientTelephone,
    this.clientEmail,
  });

  factory Reparation.fromJson(Map<String, dynamic> json) {
    return Reparation(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      numero: json['numero']?.toString() ?? '',
      appareil: json['appareil']?.toString() ?? '',
      marque: json['marque']?.toString(),
      modele: json['modele']?.toString(),
      probleme: json['probleme']?.toString(),
      status: json['statut']?.toString() ?? json['status']?.toString() ?? 'inconnu',
      prix: json['prix'] != null ? double.tryParse(json['prix'].toString()) : null,
      dateCreation: json['date_creation'] != null 
          ? DateTime.tryParse(json['date_creation'].toString()) 
          : null,
      dateModification: json['date_modification'] != null 
          ? DateTime.tryParse(json['date_modification'].toString()) 
          : null,
      dateLivraison: json['date_livraison'] != null 
          ? DateTime.tryParse(json['date_livraison'].toString()) 
          : null,
      clientId: json['client_id'] != null 
          ? (json['client_id'] is int ? json['client_id'] : int.tryParse(json['client_id'].toString()))
          : null,
      clientNom: json['client_nom']?.toString(),
      clientPrenom: json['client_prenom']?.toString(),
      clientTelephone: json['client_telephone']?.toString(),
      clientEmail: json['client_email']?.toString(),
    );
  }

  String get clientFullName {
    final parts = [clientPrenom, clientNom].where((p) => p != null && p.isNotEmpty);
    return parts.join(' ');
  }

  String get statusLabel {
    switch (status) {
      case 'en_attente':
        return 'En attente';
      case 'en_cours':
        return 'En cours';
      case 'terminee':
        return 'Terminée';
      case 'livre':
        return 'Livrée';
      case 'annulee':
        return 'Annulée';
      default:
        return status;
    }
  }
  
  // Alias for status (used in some screens)
  String get statutStr => status;

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'numero': numero,
      'type_appareil': appareil, // mapping appareil to type_appareil often used
      'marque': marque,
      'modele': modele,
      'description_probleme': probleme,
      'statut': status,
      'prix_reparation': prix,
      'date_reception': dateCreation?.toIso8601String(),
      'date_modification': dateModification?.toIso8601String(),
      'date_livraison': dateLivraison?.toIso8601String(),
      'client_id': clientId,
      'client_nom': clientNom,
      'client_prenom': clientPrenom,
      'client_telephone': clientTelephone,
      'client_email': clientEmail,
    };
  }
}
