/// Modèle Shop - Représente un magasin

class Shop {
  final int id;
  final String name;
  final String subdomain;

  Shop({
    required this.id,
    required this.name,
    required this.subdomain,
  });

  factory Shop.fromJson(Map<String, dynamic> json) {
    return Shop(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      name: json['name'] ?? '',
      // Use 'subdomain' if present, otherwise 'name' is the subdomain
      subdomain: json['subdomain'] ?? json['name'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'subdomain': subdomain,
    };
  }
}
