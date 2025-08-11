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
-- Table structure for table `quiz_nivelamento`
--

DROP TABLE IF EXISTS `quiz_nivelamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_nivelamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idioma` varchar(20) NOT NULL,
  `pergunta` text NOT NULL,
  `alternativa_a` varchar(255) NOT NULL,
  `alternativa_b` varchar(255) NOT NULL,
  `alternativa_c` varchar(255) NOT NULL,
  `alternativa_d` varchar(255) NOT NULL,
  `resposta_correta` varchar(1) NOT NULL,
  `nivel` varchar(10) DEFAULT 'A1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quiz_nivelamento`
--

LOCK TABLES `quiz_nivelamento` WRITE;
/*!40000 ALTER TABLE `quiz_nivelamento` DISABLE KEYS */;
INSERT INTO `quiz_nivelamento` VALUES (1,'Ingles','Where are you from?','I am from Italy.','I am fine, thank you.','I am 25 years old.','I like coffee.','A','A1'),(2,'Ingles','What is the correct plural form of \'cat\'?','cats','cates','catt','catts','A','A1'),(3,'Ingles','Is this your book?','Yes, it is my.','Yes, it is a book.','No, it isn\'t a book.','Yes, it is mine.','D','A1'),(4,'Ingles','They ___ happy.','is','are','am','be','B','A1'),(5,'Ingles','___ is this woman? She is my sister.','What','Who','Where','When','B','A1'),(6,'Ingles','This is a chair and that is ___ table.','a','an','the','some','A','A1'),(7,'Ingles','My friend and I ___ students.','am','is','are','be','C','A1'),(8,'Ingles','I have a dog. ___ name is Max.','He','His','She','Its','D','A1'),(9,'Ingles','He ___ to the cinema yesterday.','go','goes','went','gone','C','A2'),(10,'Ingles','___ you ever been to Paris?','Do','Did','Have','Has','C','A2'),(11,'Ingles','She usually ___ her homework in the evening.','do','does','did','doing','B','A2'),(12,'Ingles','I ___ watch TV after dinner.','not','no','don\'t','doesn\'t','C','A2'),(13,'Ingles','They are talking ___ the new movie.','about','on','with','in','A','A2'),(14,'Ingles','Could you tell me what time it is, ___?','please','with please','of please','can you','A','A2'),(15,'Ingles','If I had known you were coming, I ___ have baked a cake.','would','will','had','would have','D','B1'),(16,'Ingles','The news ___ very surprising.','is','are','was','were','C','B1'),(17,'Ingles','She wishes she ___ more money.','has','had','have','would have','B','B1'),(18,'Ingles','I\'m so tired! I ___ all night.','am working','work','have been working','worked','C','B1'),(19,'Ingles','He arrived ___ the party late.','in','at','on','to','B','B1'),(20,'Ingles','He apologized ___ being late.','for','to','on','about','A','B1'),(21,'Japones','Watashi wa Carlos desu. (Eu sou o Carlos.)','Watashi','Carlos','desu','wa','B','A1'),(22,'Japones','Kore wa hon desu. (Isto é um livro.)','Kore','wa','hon','desu','C','A1'),(23,'Japones','Anata no namae wa nan desu ka?','Watashi','Anata','namae','Nan','D','A1'),(24,'Japones','Sumimasen, toire wa doko desu ka?','Sumimasen','toire','doko','desu ka','B','A1'),(25,'Japones','Kochira koso. (O prazer é meu.)','Kochira','koso','O prazer','É meu','A','A1'),(26,'Japones','Ima, nanji desu ka?','Ima','nanji','desu ka','tempo','A','A1'),(27,'Japones','O-genki desu ka?','O-genki','desu ka','bem','feliz','C','A1'),(28,'Japones','Arigatou gozaimasu.','Obrigado','Com licença','De nada','Olá','A','A1'),(29,'Japones','Watashi wa sushi ___ tabemasu.','ni','o','de','e','B','A2'),(30,'Japones','Kono hon wa totemo omoshiroi desu ne!','totemo','omoshiroi','desu ne','Kono','B','A2'),(31,'Japones','Mainichi nihongo o benkyou ___.','shimasu','shiteimasu','suru','shita','A','A2'),(32,'Japones','Ashita, tomodachi ___ eiga o mimasu.','o','ni','to','de','C','A2'),(33,'Japones','Mado ___ akete mo ii desu ka?','wa','o','ga','de','B','A2'),(34,'Japones','Konshuu no nichiyoubi ni, tomodachi ___ aimasu.','ga','ni','to','e','B','A2'),(35,'Japones','Densha ni noru ___ kippu o kaimasu.','mae ni','ato de','tame ni','toki','A','B1'),(36,'Japones','Benkyou shinakereba ___.','narimasen','desu','narimasu','shimashita','A','B1'),(37,'Japones','Nihon no eiga ___ mita koto ga arimasu.','o','ni','ga','wa','B','B1'),(38,'Japones','Kono michi ___ ikimasu.','wo','o','ni','ga','B','B1'),(39,'Japones','Kare wa osoku natte mo, watashi ___ matte imasu.','ga','o','wo','ni','D','B1'),(40,'Japones','Kono tokei wa takasugiru ___ kaimasen.','node','noni','kara','shi','A','B1');
/*!40000 ALTER TABLE `quiz_nivelamento` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-08-11 10:57:25
