-- MySQL dump 10.13  Distrib 8.0.41, for Win64 (x86_64)
--
-- Host: localhost    Database: site_idiomas
-- ------------------------------------------------------
-- Server version	8.0.41

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `resultado_atividade_bloco`
--

DROP TABLE IF EXISTS `resultado_atividade_bloco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resultado_atividade_bloco` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_progresso_bloco` int NOT NULL,
  `id_exercicio` int NOT NULL,
  `acertou` tinyint(1) DEFAULT '0',
  `pontos_obtidos` int DEFAULT '0',
  `tempo_gasto` int DEFAULT '0',
  `tentativas` int DEFAULT '1',
  `data_conclusao` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_progresso_bloco` (`id_progresso_bloco`),
  KEY `id_exercicio` (`id_exercicio`),
  CONSTRAINT `resultado_atividade_bloco_ibfk_1` FOREIGN KEY (`id_progresso_bloco`) REFERENCES `progresso_bloco` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resultado_atividade_bloco_ibfk_2` FOREIGN KEY (`id_exercicio`) REFERENCES `exercicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `resultado_atividade_bloco`
--

LOCK TABLES `resultado_atividade_bloco` WRITE;
/*!40000 ALTER TABLE `resultado_atividade_bloco` DISABLE KEYS */;
/*!40000 ALTER TABLE `resultado_atividade_bloco` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-29 16:09:02
