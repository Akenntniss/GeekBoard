/// Modèle Client - Représente un client

class Client {
  final int id;
  final String nom;
  final String? prenom;
  final String? telephone;
  final String? email;
  final String? adresse;
  final DateTime? dateCreation;
  final int? nbReparations;
  final DateTime? derniereReparation;

  Client({
    required this.id,
    required this.nom,
    this.prenom,
    this.telephone,
    this.email,
    this.adresse,
    this.dateCreation,
    this.nbReparations,
    this.derniereReparation,
  });

  factory Client.fromJson(Map<String, dynamic> json) {
    return Client(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      nom: json['nom']?.toString() ?? '',
      prenom: json['prenom']?.toString(),
      telephone: json['telephone']?.toString(),
      email: json['email']?.toString(),
      adresse: json['adresse']?.toString(),
      dateCreation: json['date_creation'] != null 
          ? DateTime.tryParse(json['date_creation'].toString()) 
          : null,
      nbReparations: json['nb_reparations'] != null 
          ? int.tryParse(json['nb_reparations'].toString()) 
          : null,
      derniereReparation: json['derniere_reparation'] != null 
          ? DateTime.tryParse(json['derniere_reparation'].toString()) 
          : null,
    );
  }

  String get fullName {
    final parts = [prenom, nom].where((p) => p != null && p.isNotEmpty);
    return parts.join(' ');
  }
}
