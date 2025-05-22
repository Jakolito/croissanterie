<?php
$xmlFile = 'products.xml';

function loadXML() {
  global $xmlFile;
  if (!file_exists($xmlFile)) {
    file_put_contents($xmlFile, '<?xml version="1.0" encoding="UTF-8"?><products></products>');
  }
  return simplexml_load_file($xmlFile);

}
function saveXML($xml) {
  global $xmlFile;
  $xml->asXML($xmlFile);
}

header("Content-Type: application/json");
$action = $_GET['action'] ?? 'read';

if ($action === 'read') {
  $xml = loadXML();
  $products = [];
  foreach ($xml->product as $p) {
    $products[] = [
      'name' => (string)$p->name,
      'description' => (string)$p->description,
      'type' => (string)$p->type,
      'price' => (string)$p->price,
      'quantity' => (string)$p->quantity,
      'tags' => explode(",", (string)$p->tags),
      'image' => (string)$p->image
    ];
  }
  echo json_encode($products);
}

if ($action === 'add') {
  $xml = loadXML();
  $data = json_decode(file_get_contents("php://input"), true);

  $new = $xml->addChild('product');
  $new->addChild('name', $data['name']);
  $new->addChild('description', $data['description']);
  $new->addChild('type', $data['type']);
  $new->addChild('price', $data['price']);
  $new->addChild('quantity', $data['quantity']);
  $new->addChild('tags', implode(",", $data['tags']));
  $new->addChild('image', $data['image']);

  saveXML($xml);
  echo json_encode(['success' => true]);
}
