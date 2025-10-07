<?php

namespace CyberACS;

require_once __DIR__ . '/DBManager.php';

class CPE
{
  // Propriedades...
  public string $serialNumber;
  public string $manufacturer;
  public string $oui;
  public string $productClass;
  public string $hardwareVersion;

  // Construtor... (Não precisa mudar nada aqui)
  public function __construct(
    string $serialNumber,
    string $manufacturer,
    string $oui,
    string $productClass,
    string $hardwareVersion = ''
  ) {
    $this->serialNumber = $serialNumber;
    $this->manufacturer = $manufacturer;
    $this->oui = $oui;
    $this->productClass = $productClass;
    $this->hardwareVersion = $hardwareVersion;
  }

  // Método que checa se o CPE já está no MariaDB
  public function existsInDB(): bool
  {
    $pdo = DBManager::getInstance();
    $stmt = $pdo->prepare("SELECT serial_number FROM cpe_inventory WHERE serial_number = :sn");
    $stmt->execute([':sn' => $this->serialNumber]);
    return $stmt->rowCount() > 0;
  }

  // Método que salva (INSERT) ou atualiza (UPDATE) os dados no MariaDB
  public function save(): bool
  {
    $pdo = DBManager::getInstance();
    $now = date('Y-m-d H:i:s');

    if ($this->existsInDB()) {
      // Se já existe, faz UPDATE (atualiza Last Inform e versão de hardware)
      $sql = "UPDATE cpe_inventory SET 
                        manufacturer = :manu, 
                        oui = :oui, 
                        product_class = :pc, 
                        hardware_version = :hw, 
                        last_inform = :now 
                    WHERE serial_number = :sn";

      $stmt = $pdo->prepare($sql);
    } else {
      // Se NÃO existe, faz INSERT
      $sql = "INSERT INTO cpe_inventory 
                        (serial_number, manufacturer, oui, product_class, hardware_version, last_inform) 
                    VALUES (:sn, :manu, :oui, :pc, :hw, :now)";

      $stmt = $pdo->prepare($sql);
    }

    // Executa a query
    return $stmt->execute([
      ':sn' => $this->serialNumber,
      ':manu' => $this->manufacturer,
      ':oui' => $this->oui,
      ':pc' => $this->productClass,
      ':hw' => $this->hardwareVersion,
      ':now' => $now
    ]);
  }

  // Método para exibir dados (só para teste)
  public function getInfo(): string
  {
    return "CPE [{$this->manufacturer}] S/N: {$this->serialNumber} (v: {$this->hardwareVersion})";
  }
}
