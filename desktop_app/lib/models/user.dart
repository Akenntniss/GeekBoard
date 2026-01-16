/// Modèle User - Représente un utilisateur connecté

class User {
  final int id;
  final String email;
  final String nom;
  final String prenom;
  final String role;

  final int? shopId;

  User({
    required this.id,
    required this.email,
    required this.nom,
    required this.prenom,
    required this.role,
    this.shopId,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      email: json['email'] ?? '',
      nom: json['nom'] ?? '',
      prenom: json['prenom'] ?? '',
      role: json['role'] ?? 'user',
      shopId: json['shop_id'] != null 
          ? (json['shop_id'] is int ? json['shop_id'] : int.tryParse(json['shop_id'].toString()))
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'email': email,
      'nom': nom,
      'prenom': prenom,
      'role': role,
      'shop_id': shopId,
    };
  }

  String get fullName => '$prenom $nom'.trim();
  String get name => fullName;
}
