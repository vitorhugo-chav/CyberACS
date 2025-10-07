<?php

namespace CyberACS; // Namespace para organização POO

class CPE
{
  public string $serialNumber;
  public string $manufacturer;
  public string $oui;
  public string $productClass;
  public string $hardwareVersion;

  // Construtor, para iniciar o objeto
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

  // Método futuro: Vai checar se o CPE já está no MariaDB
  public function existsInDB(): bool
  {
    // Lógica de consulta ao DB
    return false;
  }

  // Método futuro: Vai salvar ou atualizar os dados no MariaDB
  public function save(): bool
  {
    // Lógica para INSERT ou UPDATE no DB
    return true;
  }

  // Método para exibir dados (só para teste)
  public function getInfo(): string
  {
    return "CPE [{$this->manufacturer}] S/N: {$this->serialNumber} (v: {$this->hardwareVersion})";
  }
}
