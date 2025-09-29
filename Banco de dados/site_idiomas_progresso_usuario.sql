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
-- Table structure for table `progresso_usuario`
--

DROP TABLE IF EXISTS `progresso_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `progresso_usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `idioma` varchar(20) NOT NULL,
  `nivel` varchar(10) NOT NULL,
  `caminho_id` int DEFAULT NULL,
  `exercicio_atual` int NOT NULL DEFAULT '1',
  `concluido` tinyint(1) NOT NULL DEFAULT '0',
  `progresso` decimal(5,2) DEFAULT '0.00',
  `data_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `ultima_atividade` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_progresso` (`id_usuario`,`caminho_id`),
  CONSTRAINT `progresso_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progresso_usuario`
--

LOCK TABLES `progresso_usuario` WRITE;
/*!40000 ALTER TABLE `progresso_usuario` DISABLE KEYS */;
INSERT INTO `progresso_usuario` VALUES (1,1,'Ingles','A1',0,1,0,0.00,'2025-09-01 15:48:25','2025-09-01 15:48:25'),(2,2,'Ingles','A2',0,1,0,0.00,'2025-09-01 15:48:25','2025-09-01 15:48:25'),(3,4,'Ingles','B1',0,1,0,0.00,'2025-09-01 15:48:25','2025-09-01 15:48:25'),(4,5,'Ingles','A1',NULL,1,0,0.00,'2025-09-01 15:48:25','2025-09-01 15:48:25');
/*!40000 ALTER TABLE `progresso_usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-29  8:18:52
