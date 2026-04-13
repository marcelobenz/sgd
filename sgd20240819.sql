-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: sgd
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categorias`
--

DROP TABLE IF EXISTS `categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categorias`
--

LOCK TABLES `categorias` WRITE;
/*!40000 ALTER TABLE `categorias` DISABLE KEYS */;
INSERT INTO `categorias` VALUES (1,'Capacitacion','2024-08-04 01:13:02','2024-08-04 01:13:04'),(2,'Auditorias','2024-08-07 22:48:24','2024-08-07 22:48:21'),(3,'Confidencialidad','2024-08-16 14:24:48','2024-08-16 14:24:50'),(4,'Clima Laboral','2024-08-16 14:25:28','2024-08-16 14:25:29');
/*!40000 ALTER TABLE `categorias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_permisos`
--

DROP TABLE IF EXISTS `documento_permisos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_permisos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `documento_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `puede_leer` tinyint(1) NOT NULL DEFAULT '0',
  `puede_escribir` tinyint(1) NOT NULL DEFAULT '0',
  `puede_aprobar` tinyint(1) NOT NULL DEFAULT '0',
  `puede_eliminar` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documento_permisos_documento_id_foreign` (`documento_id`),
  KEY `documento_permisos_user_id_foreign` (`user_id`),
  CONSTRAINT `documento_permisos_documento_id_foreign` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documento_permisos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_permisos`
--

LOCK TABLES `documento_permisos` WRITE;
/*!40000 ALTER TABLE `documento_permisos` DISABLE KEYS */;
INSERT INTO `documento_permisos` VALUES (1,36,1,1,0,1,0,'2024-08-18 01:00:17','2024-08-18 01:00:17');
/*!40000 ALTER TABLE `documento_permisos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documento_versiones`
--

DROP TABLE IF EXISTS `documento_versiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_versiones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento_id` bigint unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci,
  `version` int NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documento_versiones_documento_id_foreign` (`documento_id`),
  CONSTRAINT `documento_versiones_documento_id_foreign` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documento_versiones`
--

LOCK TABLES `documento_versiones` WRITE;
/*!40000 ALTER TABLE `documento_versiones` DISABLE KEYS */;
/*!40000 ALTER TABLE `documento_versiones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documentos`
--

DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('en curso','pendiente de aprobación','aprobado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en curso',
  `id_categoria` bigint unsigned NOT NULL,
  `id_usr_creador` bigint unsigned NOT NULL,
  `id_usr_ultima_modif` bigint unsigned NOT NULL,
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `version` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `documentos_id_categoria_foreign` (`id_categoria`),
  KEY `documentos_id_usr_creador_foreign` (`id_usr_creador`),
  KEY `documentos_id_usr_ultima_modif_foreign` (`id_usr_ultima_modif`),
  CONSTRAINT `documentos_id_categoria_foreign` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_id_usr_creador_foreign` FOREIGN KEY (`id_usr_creador`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documentos_id_usr_ultima_modif_foreign` FOREIGN KEY (`id_usr_ultima_modif`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documentos`
--

LOCK TABLES `documentos` WRITE;
/*!40000 ALTER TABLE `documentos` DISABLE KEYS */;
INSERT INTO `documentos` VALUES (9,'Prueba pdf','documentos/JFQLYTieXRxgEhYTb2Bp4kuA6kLwvxHD7aEjDK8M.docx','Prueba pdf','pendiente de aprobación',1,1,1,NULL,'2024-08-07 22:43:33','2024-08-10 17:57:44',4),(11,'Plan auditoria 2024','documentos/3980fAoDq6xMMNbK8V7QoiQzPKMpHcmzRM4lBpkW.pdf','Plan auditoria 2024','aprobado',2,1,2,NULL,'2024-08-09 00:32:05','2024-08-10 17:29:28',11),(13,'Versionado','documentos/nYU0LLBKVK7rQrMY6jSq7D0DwciIpt57DdSBiKtO.pdf','Versionado','en curso',1,1,1,NULL,'2024-08-09 01:35:33','2024-08-09 01:46:44',2),(14,'Versionado prueba','documentos/FcIOiXV0tsSjRMt8GCXi5nRNnHpLwSNawBP2IOb7.pdf','Versionado prueba','en curso',4,1,1,NULL,'2024-08-09 01:47:29','2024-08-09 02:00:22',2),(15,'Versionando','documentos/cL4v9Opi0N2RN6ncOcWWM2sQ4niRF0T4wVWtecrh.pdf','Versionando','en curso',3,1,1,NULL,'2024-08-09 02:00:58','2024-08-09 02:07:31',2),(17,'vers','documentos/4tPG5rnjKNIgOOCNba9M8AyLshV9odajuxBnCfus.pdf','vers','en curso',4,1,1,NULL,'2024-08-09 02:26:24','2024-08-10 17:22:48',1),(18,'versi','documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','en curso',3,1,1,NULL,'2024-08-09 02:29:24','2024-08-09 02:43:11',7),(19,'versio','documentos/DJcvdg36IOEF6yqSmDn8Btbuz0TfdfI8y4EEfOwN.pdf','versio','en curso',4,1,1,NULL,'2024-08-09 02:43:39','2024-08-09 02:44:28',3),(23,'09081232','documentos/fxLQZAHesLc7bWtdK5qg9EqrOG5BL0XM2cMwfyLP.pdf','09081232','en curso',1,1,1,NULL,'2024-08-09 15:32:53','2024-08-09 15:41:58',3),(24,'09081244','documentos/fGbgm4dvynctJVeaGaH4XypcnxH1UrQ3xejPar5n.pdf','09081244','en curso',2,1,1,NULL,'2024-08-09 15:44:46','2024-08-09 15:52:59',12),(25,'09081255','documentos/pQYK3gw1tHCxQ8k82KlWfp11NMCIfUI3AnXpeQZl.pdf','09081255','en curso',1,1,1,NULL,'2024-08-09 15:55:22','2024-08-09 15:57:24',2),(26,'09081301','documentos/XT2BkioF81HV04lQ4y1xYQRYuJCfjxY4oC3aor7Z.pdf','09081301','en curso',1,1,1,NULL,'2024-08-09 16:01:13','2024-08-09 16:06:32',1),(27,'09081308','documentos/WMcbcu5ux9QlwCeoqLZbWnHEP7ZxByHA9PQbZCKc.pdf','09081308','en curso',1,1,1,NULL,'2024-08-09 16:09:04','2024-08-10 00:18:04',1),(29,'Subido a s3','documentos/5pzVgUC5hslQWIGSohUqv872e78JZMJdtnCeAxf4.pdf','MIPBA prop','pendiente de aprobación',1,1,1,NULL,'2024-08-10 19:45:07','2024-08-12 00:46:24',4),(30,'pdf en s3','documentos/4TKAoVbzRVgcEwEkyI6StQu5XCOnpjx4ZiM8WBVM.pdf','pdf en s3','pendiente de aprobación',2,1,1,NULL,'2024-08-12 00:50:31','2024-08-16 15:50:01',1),(35,'Prueba mismo nombre otra version','documentos/PxJxeM1Wuz4SZRgUqHvXEiWmKhJkojGLptRn2lmd.pdf','Prueba mismo nombre otra version','pendiente de aprobación',1,1,1,NULL,'2024-08-16 10:58:49','2024-08-16 11:03:20',2),(36,'Prueba con permisos','documentos/IR3Zjsyam0aHc8JqiBbMix9UAoDFd6Hd3PiWzJcI.pdf','Prueba con permisos','aprobado',4,1,1,'2024-08-18 01:03:57','2024-08-18 01:00:17','2024-08-18 01:03:57',0);
/*!40000 ALTER TABLE `documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_documentos`
--

DROP TABLE IF EXISTS `historial_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_documentos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('en curso','pendiente de aprobación','aprobado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en curso',
  `id_documento` bigint unsigned NOT NULL,
  `id_categoria` bigint unsigned NOT NULL,
  `id_usr_creador` bigint unsigned NOT NULL,
  `id_usr_ultima_modif` bigint unsigned NOT NULL,
  `fecha_aprobacion` timestamp NULL DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `version` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `historial_documentos_id_documento_foreign` (`id_documento`),
  KEY `historial_documentos_id_categoria_foreign` (`id_categoria`),
  KEY `historial_documentos_id_usr_creador_foreign` (`id_usr_creador`),
  KEY `historial_documentos_id_usr_ultima_modif_foreign` (`id_usr_ultima_modif`),
  CONSTRAINT `historial_documentos_id_categoria_foreign` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_documentos_id_documento_foreign` FOREIGN KEY (`id_documento`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_documentos_id_usr_creador_foreign` FOREIGN KEY (`id_usr_creador`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_documentos_id_usr_ultima_modif_foreign` FOREIGN KEY (`id_usr_ultima_modif`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_documentos`
--

LOCK TABLES `historial_documentos` WRITE;
/*!40000 ALTER TABLE `historial_documentos` DISABLE KEYS */;
INSERT INTO `historial_documentos` VALUES (1,'documentos/cDfImMUmDeQlhNtCccIVRmOwxOtxMhkLUuFiTOP0.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-08 21:48:23','2024-08-08 21:48:23',0),(2,'documentos/cDfImMUmDeQlhNtCccIVRmOwxOtxMhkLUuFiTOP0.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-08 22:33:13','2024-08-08 22:33:13',1),(3,'documentos/cDfImMUmDeQlhNtCccIVRmOwxOtxMhkLUuFiTOP0.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-08 22:33:16','2024-08-08 22:33:16',2),(4,'documentos/cDfImMUmDeQlhNtCccIVRmOwxOtxMhkLUuFiTOP0.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-08 23:07:34','2024-08-08 23:07:34',3),(5,'documentos/cDfImMUmDeQlhNtCccIVRmOwxOtxMhkLUuFiTOP0.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-08 23:44:09','2024-08-08 23:44:09',4),(6,'documentos/jC5o3caIUqVCYQODXHPb3wvpR7lQInKbGRmfcfYW.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-08 23:47:11','2024-08-08 23:47:11',5),(7,'documentos/cDfImMUmDeQlhNtCccIVRmOwxOtxMhkLUuFiTOP0.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-09 00:36:21','2024-08-09 00:36:21',9),(8,'documentos/QheYbZLy8vdGYMHR5MqdY8ZurVWA311OHgrJ37Bj.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-09 01:22:29','2024-08-09 01:22:29',10),(12,'documentos/dTrBLs1XkfWdjAq20GfbxU3zSGHhPW2fuYdxPInO.pdf','Versionado','Versionado','en curso',13,1,1,1,NULL,NULL,'2024-08-09 01:35:53','2024-08-09 01:35:53',0),(13,'documentos/Brw0c4JLqE8Wr9129G54M5xDdet5aRSKIobhBpPb.pdf','Versionado','Versionado','en curso',13,1,1,1,NULL,NULL,'2024-08-09 01:37:26','2024-08-09 01:37:26',1),(14,'documentos/Brw0c4JLqE8Wr9129G54M5xDdet5aRSKIobhBpPb.pdf','Versionado','Versionado','en curso',13,1,1,1,NULL,NULL,'2024-08-09 01:46:44','2024-08-09 01:46:44',1),(15,'documentos/mixQzN8TuWdYcPOYxWJqnklqd9QKHkMAEyYJxIZp.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:47:46','2024-08-09 01:47:46',0),(16,'documentos/FcIOiXV0tsSjRMt8GCXi5nRNnHpLwSNawBP2IOb7.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:48:06','2024-08-09 01:48:06',1),(17,'documentos/PC7PaXuTKD2oCu3OxxKS6HkodZEsz6hBbNsJ01wW.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:48:18','2024-08-09 01:48:18',2),(18,'documentos/FcIOiXV0tsSjRMt8GCXi5nRNnHpLwSNawBP2IOb7.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:49:42','2024-08-09 01:49:42',1),(19,'documentos/PC7PaXuTKD2oCu3OxxKS6HkodZEsz6hBbNsJ01wW.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:53:41','2024-08-09 01:53:41',2),(20,'documentos/PC7PaXuTKD2oCu3OxxKS6HkodZEsz6hBbNsJ01wW.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:54:22','2024-08-09 01:54:22',3),(21,'documentos/PC7PaXuTKD2oCu3OxxKS6HkodZEsz6hBbNsJ01wW.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:54:44','2024-08-09 01:54:44',4),(22,'documentos/PC7PaXuTKD2oCu3OxxKS6HkodZEsz6hBbNsJ01wW.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 01:58:54','2024-08-09 01:58:54',5),(23,'documentos/PC7PaXuTKD2oCu3OxxKS6HkodZEsz6hBbNsJ01wW.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 02:00:13','2024-08-09 02:00:13',6),(24,'documentos/FcIOiXV0tsSjRMt8GCXi5nRNnHpLwSNawBP2IOb7.pdf','Versionado prueba','Versionado prueba','en curso',14,4,1,1,NULL,NULL,'2024-08-09 02:00:22','2024-08-09 02:00:22',1),(25,'documentos/95vGXpeJ7vq5p808gn7zuGXD4oyMZdm5nznckGnx.pdf','Versionando','Versionando','en curso',15,3,1,1,NULL,NULL,'2024-08-09 02:01:16','2024-08-09 02:01:16',0),(26,'documentos/cL4v9Opi0N2RN6ncOcWWM2sQ4niRF0T4wVWtecrh.pdf','Versionando','Versionando','en curso',15,3,1,1,NULL,NULL,'2024-08-09 02:01:26','2024-08-09 02:01:26',1),(27,'documentos/cL4v9Opi0N2RN6ncOcWWM2sQ4niRF0T4wVWtecrh.pdf','Versionando','Versionando','en curso',15,3,1,1,NULL,NULL,'2024-08-09 02:07:08','2024-08-09 02:07:08',2),(28,'documentos/cL4v9Opi0N2RN6ncOcWWM2sQ4niRF0T4wVWtecrh.pdf','Versionando','Versionando','en curso',15,3,1,1,NULL,NULL,'2024-08-09 02:07:31','2024-08-09 02:07:31',1),(38,'documentos/4h1PxqgCFX31XW4xkwvgQ6pYRYPZf8ux5zJrdaI7.pdf','vers','vers','en curso',17,4,1,1,NULL,NULL,'2024-08-09 02:26:44','2024-08-09 02:26:44',0),(39,'documentos/4tPG5rnjKNIgOOCNba9M8AyLshV9odajuxBnCfus.pdf','vers','vers','en curso',17,4,1,1,NULL,NULL,'2024-08-09 02:26:57','2024-08-09 02:26:57',1),(40,'documentos/4tPG5rnjKNIgOOCNba9M8AyLshV9odajuxBnCfus.pdf','vers','vers','en curso',17,4,1,1,NULL,NULL,'2024-08-09 02:29:02','2024-08-09 02:29:02',2),(41,'documentos/4tPG5rnjKNIgOOCNba9M8AyLshV9odajuxBnCfus.pdf','vers','vers','en curso',17,4,1,1,NULL,NULL,'2024-08-09 02:29:05','2024-08-09 02:29:05',3),(42,'documentos/6T0DmJId4izKCK4MrWDxBud1gDJw1zjm5Q5F4rt9.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:29:37','2024-08-09 02:29:37',0),(43,'documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:29:42','2024-08-09 02:29:42',1),(44,'documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:33:40','2024-08-09 02:33:40',2),(45,'documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:34:26','2024-08-09 02:34:26',3),(46,'documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:38:48','2024-08-09 02:38:48',4),(47,'documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:43:08','2024-08-09 02:43:08',5),(48,'documentos/T9vJy5dL2p5N5D9ut1wyXFkqnssWLC5Bg5bBPX8h.pdf','versi','versi','en curso',18,3,1,1,NULL,NULL,'2024-08-09 02:43:11','2024-08-09 02:43:11',6),(49,'documentos/O2zM7A38chXX27Cl91fmvhwpaOrNhArpR66xi0Ji.pdf','versio','versio','en curso',19,4,1,1,NULL,NULL,'2024-08-09 02:43:52','2024-08-09 02:43:52',0),(50,'documentos/OYaLEWdbBvg4O6oWEse7ffA6qDX6oyf7hOAcb8Ir.pdf','versio','versio','en curso',19,4,1,1,NULL,NULL,'2024-08-09 02:44:16','2024-08-09 02:44:16',1),(51,'documentos/DJcvdg36IOEF6yqSmDn8Btbuz0TfdfI8y4EEfOwN.pdf','versio','versio','en curso',19,4,1,1,NULL,NULL,'2024-08-09 02:44:28','2024-08-09 02:44:28',2),(65,'documentos/tzIwd1ibMjAuiNGq2gIj5Vf5kbrL9LrhRbJVncan.pdf','09081232','09081232','en curso',23,1,1,1,NULL,NULL,'2024-08-09 15:33:07','2024-08-09 15:33:07',0),(66,'documentos/KrABl0t7mIchgxUxYxiomwEWIhB5DGKxOmql6P3N.pdf','09081232','09081232','en curso',23,1,1,1,NULL,NULL,'2024-08-09 15:35:45','2024-08-09 15:35:45',1),(67,'documentos/FL7EFH0jryofUy6NbnoQzvOaYijK1cV8AMOoJf8L.pdf','09081232','09081232','en curso',23,1,1,1,NULL,NULL,'2024-08-09 15:41:22','2024-08-09 15:41:22',2),(68,'documentos/FL7EFH0jryofUy6NbnoQzvOaYijK1cV8AMOoJf8L.pdf','09081232','09081232','en curso',23,1,1,1,NULL,NULL,'2024-08-09 15:41:58','2024-08-09 15:41:58',2),(69,'documentos/ZYPWiEbtjNrD0ZvFxBXRPCm1ErFFej0vCdqHc9vh.pdf','09081244','09081244','en curso',24,2,1,1,NULL,NULL,'2024-08-09 15:44:58','2024-08-09 15:44:58',0),(70,'documentos/XZaILtNtRXKT85At8HVTpnI7aAqqIDrJdr8yoKgH.pdf','09081244','09081244','en curso',24,2,1,1,NULL,NULL,'2024-08-09 15:45:22','2024-08-09 15:45:22',1),(71,'documentos/LISZqeYeOqemZcjINu0DqQTEppMMgKbLuFV6Ok9I.pdf','09081244','09081244','en curso',24,2,1,1,NULL,NULL,'2024-08-09 15:45:42','2024-08-09 15:45:42',2),(72,'documentos/vulnaHEpe0EN0BynI1WqkE3xUNkvXsgI8vo5XHUc.pdf','09081255','09081255','en curso',25,1,1,1,NULL,NULL,'2024-08-09 15:55:34','2024-08-09 15:55:34',0),(73,'documentos/2ZF2Zac2uGI7yVrs3MbNz1tCSYSvAmu8P2OVgeFb.pdf','09081255','09081255','en curso',25,1,1,1,NULL,NULL,'2024-08-09 15:56:39','2024-08-09 15:56:39',1),(74,'documentos/yRnyrQX4rOhBsez5jTddWQ3IgVoHPwbQVOXzHTow.pdf','09081255','09081255','en curso',25,1,1,1,NULL,NULL,'2024-08-09 15:56:49','2024-08-09 15:56:49',2),(75,'documentos/i4Hq6a9QSOluInOEpmTI7Hf7LWDtDmZu7sC76Reg.pdf','09081301','09081301','en curso',26,1,1,1,NULL,NULL,'2024-08-09 16:01:49','2024-08-09 16:01:49',0),(76,'documentos/XT2BkioF81HV04lQ4y1xYQRYuJCfjxY4oC3aor7Z.pdf','09081301','09081301','en curso',26,1,1,1,NULL,NULL,'2024-08-09 16:02:36','2024-08-09 16:02:36',1),(77,'documentos/a2TpJi3DjnYx6Rz82QXuUiibRKxOIy00txwNrIKJ.pdf','09081301','09081301','en curso',26,1,1,1,NULL,NULL,'2024-08-09 16:03:28','2024-08-09 16:03:28',2),(78,'documentos/rKSFLhzhQMjgwTBnd2JoJiZiBR0gu3WGaUwVEjR0.pdf','09081308','09081308','en curso',27,1,1,1,NULL,NULL,'2024-08-09 16:09:15','2024-08-09 16:09:15',0),(79,'documentos/WMcbcu5ux9QlwCeoqLZbWnHEP7ZxByHA9PQbZCKc.pdf','09081308','09081308','en curso',27,1,1,1,NULL,NULL,'2024-08-09 16:09:26','2024-08-09 16:09:26',1),(80,'documentos/vIpbAU7nBHKKXHtoynL4hNmNfQoqfQ9O5GJyKx5C.pdf','09081308','09081308','en curso',27,1,1,1,NULL,NULL,'2024-08-09 16:09:30','2024-08-09 16:09:30',2),(81,'documentos/nIPOfYyVmvlRxme0hJf9KZO8DoM0ZWEDnqPAgVFi.pdf','09081308','09081308','en curso',27,1,1,1,NULL,NULL,'2024-08-09 16:10:19','2024-08-09 16:10:19',3),(82,'documentos/sTGHj0GHmC8z2um07u73Yb832dMqeBLV0nxKuxE1.pdf','09081308','09081308','en curso',27,1,1,1,NULL,NULL,'2024-08-09 16:16:40','2024-08-09 16:16:40',4),(84,'documentos/4tPG5rnjKNIgOOCNba9M8AyLshV9odajuxBnCfus.pdf','vers','vers','en curso',17,4,1,1,NULL,NULL,'2024-08-10 17:22:28','2024-08-10 17:22:28',4),(85,'documentos/3980fAoDq6xMMNbK8V7QoiQzPKMpHcmzRM4lBpkW.pdf','Plan auditoria 2024','Plan auditoria 2024','aprobado',11,2,1,2,NULL,NULL,'2024-08-10 17:29:15','2024-08-10 17:29:15',11),(86,'documentos/2FA6nKdoi2NQXI5mDU3vqMFfd9QZqQeJikvjCags.pdf','Prueba pdf','Prueba pdf','en curso',9,1,1,1,NULL,NULL,'2024-08-10 17:33:10','2024-08-10 17:33:10',0),(87,'documentos/CgBfA7kiH4tLPS7k6ZZgnlBUFe8SML0ACopf2ii5.pdf','Prueba pdf','Prueba pdf','pendiente de aprobación',9,1,1,1,NULL,NULL,'2024-08-10 17:39:59','2024-08-10 17:39:59',1),(88,'documentos/bvEgZyq2CSWeTwjTlGSt9LudQnoeN4y4Fm2Nxy0E.docx','Prueba pdf','Prueba pdf','pendiente de aprobación',9,1,1,1,NULL,NULL,'2024-08-10 17:40:35','2024-08-10 17:40:35',2),(89,'documentos/aGpBybI53Z61ntjNnd94bUdKQ8lu6Xgzl3QPjaN1.docx','Prueba pdf','Prueba pdf','pendiente de aprobación',9,1,1,1,NULL,NULL,'2024-08-10 17:57:44','2024-08-10 17:57:44',3),(90,'documentos/feerImHRq7xb6FEYh71rJuPIZBT3z7c3UKNs0T8s.docx','Subido a s3','MIPBA prop','en curso',29,1,1,1,NULL,NULL,'2024-08-10 20:53:44','2024-08-10 20:53:44',0),(91,'documentos/0nKXwl3smieTpSViOB2ldO8RDbOpTM7gQdVRmmLF.pdf','Subido a s3','MIPBA prop','pendiente de aprobación',29,1,1,1,NULL,NULL,'2024-08-10 20:55:08','2024-08-10 20:55:08',1),(92,'documentos/81KtyBWbcUidyC6bcnBUBmrbWcaxYt0NbFZUM75e.pdf','Subido a s3','MIPBA prop','pendiente de aprobación',29,1,1,1,NULL,NULL,'2024-08-10 20:57:42','2024-08-10 20:57:42',2),(93,'documentos/CNqOO4ym07zrAYzsuCYtVdhiEFDycsXa1aK0vSjU.pdf','Subido a s3','MIPBA prop','pendiente de aprobación',29,1,1,1,NULL,NULL,'2024-08-12 00:46:24','2024-08-12 00:46:24',3),(94,'documentos/r79p8y6h7kyYbCyMaZpdNUOkq3RZaQd3hhziUvie.pdf','pdf en s3','pdf en s3','en curso',30,2,1,1,NULL,NULL,'2024-08-12 00:53:08','2024-08-12 00:53:08',0),(95,'documentos/4TKAoVbzRVgcEwEkyI6StQu5XCOnpjx4ZiM8WBVM.pdf','pdf en s3','pdf en s3','pendiente de aprobación',30,2,1,1,NULL,NULL,'2024-08-12 00:56:33','2024-08-12 00:56:33',1),(96,'documentos/xQzZLxv9o3GtDNKjByJcNkuiGWJFTGcfSCxSYzSC.docx','pdf en s3','pdf en s3','pendiente de aprobación',30,2,1,1,NULL,NULL,'2024-08-12 00:57:56','2024-08-12 00:57:56',2),(97,'documentos/NjtYVbQwGF2Ld7A9VpFGXFbrvY2bZOAvcNKWoFt3.xlsx','pdf en s3','pdf en s3','pendiente de aprobación',30,2,1,1,NULL,NULL,'2024-08-12 00:58:39','2024-08-12 00:58:39',3),(98,'documentos/xQzZLxv9o3GtDNKjByJcNkuiGWJFTGcfSCxSYzSC.docx','pdf en s3','pdf en s3','pendiente de aprobación',30,2,1,1,'2024-08-13 13:49:42',NULL,'2024-08-14 16:29:20','2024-08-14 16:29:20',4),(99,'documentos/xQzZLxv9o3GtDNKjByJcNkuiGWJFTGcfSCxSYzSC.docx','pdf en s3','pdf en s3','aprobado',30,2,1,1,'2024-08-15 11:22:49',NULL,'2024-08-15 11:27:15','2024-08-15 11:27:15',5),(106,'documentos/rdyJKgOBgoqa8vNYwvWiypWYJoEYmbBoSswXVWis.pdf','Prueba mismo nombre otra version','Prueba mismo nombre otra version','en curso',35,1,1,1,NULL,NULL,'2024-08-16 11:00:19','2024-08-16 11:00:19',0),(107,'documentos/rdyJKgOBgoqa8vNYwvWiypWYJoEYmbBoSswXVWis.pdf','Prueba mismo nombre otra version','Prueba mismo nombre otra version','pendiente de aprobación',35,1,1,1,NULL,NULL,'2024-08-16 11:03:18','2024-08-16 11:03:18',1);
/*!40000 ALTER TABLE `historial_documentos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_10_12_000000_create_users_table',1),(2,'2014_10_12_100000_create_password_reset_tokens_table',1),(3,'2019_08_19_000000_create_failed_jobs_table',1),(4,'2019_12_14_000001_create_personal_access_tokens_table',1),(5,'2024_08_02_231745_create_permission_tables',2),(7,'2024_08_03_203925_create_documents_table',3),(8,'2024_08_03_201016_create_categorias_table',4),(9,'2024_08_03_203925_create_documentos_table',5),(10,'2024_08_03_210422_create_historial_documentos_table',6),(11,'2024_08_08_104326_create_document_versions_table',7),(12,'2024_08_08_104326_create_documento_versiones_table',8),(13,'2024_08_03_210423._create_historial_documentos_table',9),(14,'2024_08_03_210424._create_historial_documentos_table',10),(15,'2024_08_03_210425._create_historial_documentos_table',11),(16,'2024_08_03_210426._create_historial_documentos_table',12),(17,'2024_08_17_194707_create_documento_permisos_table',13);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Marcelo Hector','mbenz@cognisys.com.ar',NULL,'$2y$12$dDjsX8/4i6ufbDj8LxPGc.W77/kZVhoTlwNi96769jPm9IEFGPXrm',NULL,'2024-08-03 02:26:23','2024-08-16 17:49:48'),(2,'Hector','hector@cognisys.com.ar',NULL,'$2y$12$kTGEYSOecKQaCTUc1fi3T.8Hkd/Asbu1e2oGRfr9rJN3z0rsXcBuW',NULL,'2024-08-03 02:27:12','2024-08-03 02:27:12');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'sgd'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-08-19 15:47:29
