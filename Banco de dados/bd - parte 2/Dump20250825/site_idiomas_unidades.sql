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
-- Table structure for table `unidades`
--

DROP TABLE IF EXISTS `unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idioma` varchar(20) NOT NULL,
  `nivel` varchar(10) NOT NULL,
  `numero_unidade` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unidades`
--

LOCK TABLES `unidades` WRITE;
/*!40000 ALTER TABLE `unidades` DISABLE KEYS */;
INSERT INTO `unidades` VALUES (1,'Ingles','A1',1,'Cumprimentos e Apresentações','Aprenda a se apresentar e cumprimentar as pessoas em inglês.'),(2,'Ingles','A1',2,'O Verbo To Be','Domine o verbo \"to be\" e sua aplicação em frases simples.'),(3,'Ingles','A2',1,'Vocabulário de Viagem','Descubra as palavras essenciais para viajar e pedir informações.'),(4,'Ingles','A2',2,'Rotina Diária','Fale sobre sua rotina e horários usando o presente simples.'),(5,'Ingles','B1',1,'Passado Simples','Conte histórias e descreva eventos passados em inglês.'),(6,'Ingles','B1',2,'Verbos Irregulares','Estude os principais verbos irregulares e suas formas.'),(7,'Japones','A1',1,'Hiragana e Cumprimentos','Aprenda o alfabeto Hiragana e as saudações básicas.'),(8,'Japones','A1',2,'Introdução ao Katakana','Conheça o Katakana para ler palavras estrangeiras.'),(9,'Japones','A2',1,'Kanji Básico (Sol, Lua)','Introdução aos primeiros Kanjis (日, 月) e sua leitura.'),(10,'Japones','A2',2,'Comidas e Restaurantes','Aprenda a pedir pratos e falar sobre comida em japonês.');
/*!40000 ALTER TABLE `unidades` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-25 11:33:33
