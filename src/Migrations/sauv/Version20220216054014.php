<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220216054014 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE SortieAvantHeure_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Visiteur_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE CDC_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE VisiteurTest_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE LectureTransmission_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Transmission_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE TransmissionPieceJointe_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE IncidentGeneral_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Interne_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Dossier_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE Navette_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE JourFeries_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE RecolteHeure_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE SortieAvantHeure (id INT NOT NULL, date_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, matricule VARCHAR(100) NOT NULL, heureSortie TIME(0) WITHOUT TIME ZONE NOT NULL, donneurOrdre VARCHAR(100) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE Visiteur (id INT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, cin VARCHAR(100) NOT NULL, fileName VARCHAR(255) DEFAULT NULL, updatedAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, heureSortie TIME(0) WITHOUT TIME ZONE DEFAULT NULL, motif VARCHAR(255) NOT NULL, heureentrer TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE CDC (id INT NOT NULL, nom_cdc VARCHAR(255) NOT NULL, version VARCHAR(10) DEFAULT NULL, observations TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE VisiteurTest (id INT NOT NULL, description VARCHAR(255) NOT NULL, heure_entre TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE LectureTransmission (id INT NOT NULL, transmission_id INT DEFAULT NULL, destinataire INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6EB5110978D28519 ON LectureTransmission (transmission_id)');
        $this->addSql('CREATE TABLE Transmission (id INT NOT NULL, dossier_id INT DEFAULT NULL, objet VARCHAR(255) NOT NULL, date_envoie TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, contenu TEXT NOT NULL, expediteur INT NOT NULL, destinataires TEXT NOT NULL, lu BOOLEAN DEFAULT \'false\' NOT NULL, mail_client BOOLEAN DEFAULT \'false\', mail_navette BOOLEAN DEFAULT \'false\', date_reel_reception DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_67B6C267611C0C56 ON Transmission (dossier_id)');
        $this->addSql('COMMENT ON COLUMN Transmission.destinataires IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE transmission_transmission (transmission_source INT NOT NULL, transmission_target INT NOT NULL, PRIMARY KEY(transmission_source, transmission_target))');
        $this->addSql('CREATE INDEX IDX_DEE804AAAB7A113E ON transmission_transmission (transmission_source)');
        $this->addSql('CREATE INDEX IDX_DEE804AAB29F41B1 ON transmission_transmission (transmission_target)');
        $this->addSql('CREATE TABLE TransmissionPieceJointe (id INT NOT NULL, transmission_id INT DEFAULT NULL, nom_piece VARCHAR(255) NOT NULL, nom_origine VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8C6DB00178D28519 ON TransmissionPieceJointe (transmission_id)');
        $this->addSql('CREATE TABLE IncidentGeneral (id INT NOT NULL, date_incident DATE NOT NULL, heure_debut TIME(0) WITHOUT TIME ZONE NOT NULL, heure_fin TIME(0) WITHOUT TIME ZONE NOT NULL, raison TEXT NOT NULL, inserer_par INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE Interne (id INT NOT NULL, dates TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, matricule VARCHAR(255) NOT NULL, motifs VARCHAR(255) NOT NULL, heuresortie TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, donneurOrdre VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE Dossier (id INT NOT NULL, cdc_id INT NOT NULL, nom_dossier VARCHAR(255) NOT NULL, date_ajout DATE DEFAULT NULL, actif VARCHAR(10) NOT NULL, nom_mairie VARCHAR(100) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F2F5D9ABE3834915 ON Dossier (cdc_id)');
        $this->addSql('CREATE TABLE Navette (id INT NOT NULL, dossier_id INT DEFAULT NULL, date_envoie TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, contenu TEXT NOT NULL, objet VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_15E9F652611C0C56 ON Navette (dossier_id)');
        $this->addSql('CREATE TABLE JourFeries (id INT NOT NULL, date DATE NOT NULL, motif VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE RecolteHeure (id INT NOT NULL, matricule INT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) DEFAULT NULL, date_embauche DATE NOT NULL, debut_compte DATE NOT NULL, fin_compte DATE NOT NULL, heure_references DOUBLE PRECISION NOT NULL, nb_jour_references DOUBLE PRECISION NOT NULL, horaire DOUBLE PRECISION NOT NULL, heures_travailles DOUBLE PRECISION DEFAULT NULL, heures_majores DOUBLE PRECISION DEFAULT NULL, heures_recuperes DOUBLE PRECISION DEFAULT NULL, heures_supp_30 DOUBLE PRECISION DEFAULT NULL, heures_supp_nuit_75 DOUBLE PRECISION DEFAULT NULL, heures_supp_dimanche_100 DOUBLE PRECISION DEFAULT NULL, total_heures_supp DOUBLE PRECISION DEFAULT NULL, avantage_en_nature DOUBLE PRECISION DEFAULT NULL, absence_conge_paye DOUBLE PRECISION DEFAULT NULL, absence_reguliere DOUBLE PRECISION DEFAULT NULL, absence_irreguliere DOUBLE PRECISION DEFAULT NULL, indemnite_conge_paye DOUBLE PRECISION DEFAULT NULL, indemnite_repas_transport DOUBLE PRECISION DEFAULT NULL, fonction VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE LectureTransmission ADD CONSTRAINT FK_6EB5110978D28519 FOREIGN KEY (transmission_id) REFERENCES Transmission (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Transmission ADD CONSTRAINT FK_67B6C267611C0C56 FOREIGN KEY (dossier_id) REFERENCES Dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transmission_transmission ADD CONSTRAINT FK_DEE804AAAB7A113E FOREIGN KEY (transmission_source) REFERENCES Transmission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transmission_transmission ADD CONSTRAINT FK_DEE804AAB29F41B1 FOREIGN KEY (transmission_target) REFERENCES Transmission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE TransmissionPieceJointe ADD CONSTRAINT FK_8C6DB00178D28519 FOREIGN KEY (transmission_id) REFERENCES Transmission (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Dossier ADD CONSTRAINT FK_F2F5D9ABE3834915 FOREIGN KEY (cdc_id) REFERENCES CDC (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE Navette ADD CONSTRAINT FK_15E9F652611C0C56 FOREIGN KEY (dossier_id) REFERENCES Dossier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE Dossier DROP CONSTRAINT FK_F2F5D9ABE3834915');
        $this->addSql('ALTER TABLE LectureTransmission DROP CONSTRAINT FK_6EB5110978D28519');
        $this->addSql('ALTER TABLE transmission_transmission DROP CONSTRAINT FK_DEE804AAAB7A113E');
        $this->addSql('ALTER TABLE transmission_transmission DROP CONSTRAINT FK_DEE804AAB29F41B1');
        $this->addSql('ALTER TABLE TransmissionPieceJointe DROP CONSTRAINT FK_8C6DB00178D28519');
        $this->addSql('ALTER TABLE Transmission DROP CONSTRAINT FK_67B6C267611C0C56');
        $this->addSql('ALTER TABLE Navette DROP CONSTRAINT FK_15E9F652611C0C56');
        $this->addSql('DROP SEQUENCE SortieAvantHeure_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE Visiteur_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE CDC_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE VisiteurTest_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE LectureTransmission_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE Transmission_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE TransmissionPieceJointe_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE IncidentGeneral_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE Interne_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE Dossier_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE Navette_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE JourFeries_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE RecolteHeure_id_seq CASCADE');
        $this->addSql('DROP TABLE SortieAvantHeure');
        $this->addSql('DROP TABLE Visiteur');
        $this->addSql('DROP TABLE CDC');
        $this->addSql('DROP TABLE VisiteurTest');
        $this->addSql('DROP TABLE LectureTransmission');
        $this->addSql('DROP TABLE Transmission');
        $this->addSql('DROP TABLE transmission_transmission');
        $this->addSql('DROP TABLE TransmissionPieceJointe');
        $this->addSql('DROP TABLE IncidentGeneral');
        $this->addSql('DROP TABLE Interne');
        $this->addSql('DROP TABLE Dossier');
        $this->addSql('DROP TABLE Navette');
        $this->addSql('DROP TABLE JourFeries');
        $this->addSql('DROP TABLE RecolteHeure');
    }
}
