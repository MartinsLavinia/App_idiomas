import React, { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { Button } from '@/components/ui/button.jsx'
import { Card, CardContent } from '@/components/ui/card.jsx'
import {
    ChevronLeft,
    ChevronRight,
    Play,
    Star,
    Users,
    Globe,
    BookOpen,
    Gamepad2,
    MessageCircle,
    ArrowDown,
    Menu,
    X
} from 'lucide-react'
import './App.css'

function App() {
    const [currentSlide, setCurrentSlide] = useState(0)
    const [currentContentSlide, setCurrentContentSlide] = useState(0)
    const [isMenuOpen, setIsMenuOpen] = useState(false)
    const [scrollY, setScrollY] = useState(0)

    // Controle do scroll para efeitos parallax
    useEffect(() => {
        const handleScroll = () => setScrollY(window.scrollY)
        window.addEventListener('scroll', handleScroll)
        return () => window.removeEventListener('scroll', handleScroll)
    }, [])

    // Auto-rotate para o carrossel de conteúdo
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentContentSlide((prev) => (prev + 1) % 3)
        }, 4000)
        return () => clearInterval(interval)
    }, [])

    // Auto-rotate para o carrossel principal
    useEffect(() => {
        const interval = setInterval(() => {
            setCurrentSlide((prev) => (prev + 1) % 3)
        }, 5000)
        return () => clearInterval(interval)
    }, [])

    const carouselImages = [
        { id: 1, title: "Aprenda no seu ritmo", description: "Aulas personalizadas para seu nível" },
        { id: 2, title: "Professores nativos", description: "Aprenda com quem domina o idioma" },
        { id: 3, title: "Gamificação", description: "Torne o aprendizado divertido" }
    ]

    const contentSlides = [
        {
            title: "Estrutura de Aprendizado Personalizada",
            description: "Cada unidade foi cuidadosamente desenvolvida para proporcionar uma experiência de aprendizado completa e eficaz. Nossa metodologia garante que você absorva o conhecimento de forma gradual e consistente.",
            icon: <BookOpen className="w-16 h-16 text-purple-600" />,
            subtitle: "Conteúdo teórico para você estudar no seu ritmo"
        },
        {
            title: "Minigames Interativos",
            description: "Transforme seu aprendizado em diversão com nossos minigames educativos! Pratique vocabulário, gramática e pronúncia de forma lúdica. Cada minigame é projetado para reforçar conceitos específicos.",
            icon: <Gamepad2 className="w-16 h-16 text-purple-600" />,
            subtitle: "Minigames para praticar e testar seus conhecimentos"
        },
        {
            title: "Perguntas e Respostas Interativas",
            description: "Desenvolva suas habilidades através de sessões interativas de perguntas e respostas com feedback instantâneo. Nosso sistema adapta as questões ao seu nível, proporcionando desafios adequados.",
            icon: <MessageCircle className="w-16 h-16 text-purple-600" />,
            subtitle: "Perguntas e respostas interativas"
        }
    ]

    const scrollToSection = (sectionId) => {
        document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth' })
    }

    return (
        <div className="min-h-screen bg-gradient-to-br from-purple-900 via-purple-800 to-indigo-900 overflow-x-hidden">
            {/* Header fixo com animação */}
            <motion.header
                className="fixed top-0 left-0 right-0 z-50 bg-white/10 backdrop-blur-md border-b border-white/20"
                initial={{ y: -100 }}
                animate={{ y: 0 }}
                transition={{ duration: 0.6 }}
            >
                <div className="container mx-auto px-4 py-4 flex justify-between items-center">
                    <motion.div
                        className="text-2xl font-bold text-white"
                        whileHover={{ scale: 1.05 }}
                        transition={{ type: "spring", stiffness: 400 }}
                    >
                        SpeakNut
                    </motion.div>

                    {/* Menu desktop */}
                    <div className="hidden md:flex space-x-4">
                        <Button
                            variant="ghost"
                            className="text-white hover:bg-white/20"
                            onClick={() => scrollToSection('welcome')}
                        >
                            Início
                        </Button>
                        <Button
                            variant="ghost"
                            className="text-white hover:bg-white/20"
                            onClick={() => scrollToSection('main')}
                        >
                            Sobre
                        </Button>
                        <Button
                            variant="ghost"
                            className="text-white hover:bg-white/20"
                            onClick={() => scrollToSection('units')}
                        >
                            Unidades
                        </Button>
                        <Button className="bg-purple-600 hover:bg-purple-700 text-white">
                            Entrar
                        </Button>
                        <Button variant="outline" className="border-white text-white hover:bg-white hover:text-purple-900">
                            Cadastre-se
                        </Button>
                    </div>

                    {/* Menu mobile */}
                    <div className="md:hidden">
                        <Button
                            variant="ghost"
                            size="icon"
                            className="text-white"
                            onClick={() => setIsMenuOpen(!isMenuOpen)}
                        >
                            {isMenuOpen ? <X /> : <Menu />}
                        </Button>
                    </div>
                </div>

                {/* Menu mobile expandido */}
                <AnimatePresence>
                    {isMenuOpen && (
                        <motion.div
                            className="md:hidden bg-white/10 backdrop-blur-md border-t border-white/20"
                            initial={{ height: 0, opacity: 0 }}
                            animate={{ height: 'auto', opacity: 1 }}
                            exit={{ height: 0, opacity: 0 }}
                            transition={{ duration: 0.3 }}
                        >
                            <div className="container mx-auto px-4 py-4 space-y-2">
                                <Button
                                    variant="ghost"
                                    className="w-full text-white hover:bg-white/20 justify-start"
                                    onClick={() => {
                                        scrollToSection('welcome')
                                        setIsMenuOpen(false)
                                    }}
                                >
                                    Início
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full text-white hover:bg-white/20 justify-start"
                                    onClick={() => {
                                        scrollToSection('main')
                                        setIsMenuOpen(false)
                                    }}
                                >
                                    Sobre
                                </Button>
                                <Button
                                    variant="ghost"
                                    className="w-full text-white hover:bg-white/20 justify-start"
                                    onClick={() => {
                                        scrollToSection('units')
                                        setIsMenuOpen(false)
                                    }}
                                >
                                    Unidades
                                </Button>
                                <div className="flex space-x-2 pt-2">
                                    <Button className="flex-1 bg-purple-600 hover:bg-purple-700 text-white">
                                        Entrar
                                    </Button>
                                    <Button variant="outline" className="flex-1 border-white text-white hover:bg-white hover:text-purple-900">
                                        Cadastre-se
                                    </Button>
                                </div>
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>
            </motion.header>

            {/* Seção 1: Boas-vindas */}
            <section id="welcome" className="min-h-screen flex items-center justify-center relative overflow-hidden">
                {/* Elementos decorativos animados */}
                <div className="absolute inset-0">
                    {[...Array(20)].map((_, i) => (
                        <motion.div
                            key={i}
                            className="absolute w-2 h-2 bg-white/20 rounded-full"
                            style={{
                                left: `${Math.random() * 100}%`,
                                top: `${Math.random() * 100}%`,
                            }}
                            animate={{
                                y: [0, -20, 0],
                                opacity: [0.2, 0.8, 0.2],
                            }}
                            transition={{
                                duration: 3 + Math.random() * 2,
                                repeat: Infinity,
                                delay: Math.random() * 2,
                            }}
                        />
                    ))}
                </div>

                <div className="container mx-auto px-4 grid md:grid-cols-2 gap-12 items-center relative z-10">
                    {/* Lado esquerdo - Conteúdo */}
                    <motion.div
                        className="text-center md:text-left"
                        initial={{ opacity: 0, x: -50 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.8 }}
                    >
                        <motion.h1
                            className="text-5xl md:text-7xl font-bold text-white mb-6"
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.8, delay: 0.2 }}
                        >
                            Seja bem-vindo ao{' '}
                            <motion.span
                                className="text-yellow-400"
                                animate={{
                                    textShadow: [
                                        "0 0 10px rgba(255, 255, 0, 0.5)",
                                        "0 0 20px rgba(255, 255, 0, 0.8)",
                                        "0 0 10px rgba(255, 255, 0, 0.5)"
                                    ]
                                }}
                                transition={{ duration: 2, repeat: Infinity }}
                            >
                                SpeakNut
                            </motion.span>
                        </motion.h1>

                        <motion.p
                            className="text-xl md:text-2xl text-purple-200 mb-8"
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.8, delay: 0.4 }}
                        >
                            Sua nova plataforma de aprendizado de idiomas
                        </motion.p>

                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.8, delay: 0.6 }}
                        >
                            <Button
                                size="lg"
                                className="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white px-8 py-4 text-lg rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300"
                                onClick={() => scrollToSection('main')}
                            >
                                <Play className="w-5 h-5 mr-2" />
                                Descubra mais
                            </Button>
                        </motion.div>
                    </motion.div>

                    {/* Lado direito - Mascote animado */}
                    <motion.div
                        className="flex justify-center"
                        initial={{ opacity: 0, x: 50 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.8, delay: 0.3 }}
                    >
                        <motion.div
                            className="w-80 h-80 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-8xl shadow-2xl"
                            animate={{
                                y: [0, -20, 0],
                                rotate: [0, 5, -5, 0],
                            }}
                            transition={{
                                duration: 4,
                                repeat: Infinity,
                                ease: "easeInOut"
                            }}
                            whileHover={{ scale: 1.1 }}
                        >
                            🐿️
                        </motion.div>
                    </motion.div>
                </div>

                {/* Botão de scroll animado */}
                <motion.div
                    className="absolute bottom-8 left-1/2 transform -translate-x-1/2"
                    animate={{ y: [0, 10, 0] }}
                    transition={{ duration: 2, repeat: Infinity }}
                >
                    <Button
                        variant="ghost"
                        size="icon"
                        className="text-white hover:bg-white/20 rounded-full"
                        onClick={() => scrollToSection('main')}
                    >
                        <ArrowDown className="w-6 h-6" />
                    </Button>
                </motion.div>
            </section>

            {/* Seção 2: Conteúdo principal */}
            <section id="main" className="min-h-screen py-20 relative">
                {/* Efeito parallax no fundo */}
                <div
                    className="absolute inset-0 bg-gradient-to-br from-purple-800 to-indigo-900"
                    style={{ transform: `translateY(${scrollY * 0.5}px)` }}
                />

                <div className="container mx-auto px-4 relative z-10">
                    <div className="grid lg:grid-cols-2 gap-12 items-center mb-20">
                        {/* Carrossel de imagens */}
                        <motion.div
                            className="relative"
                            initial={{ opacity: 0, x: -50 }}
                            whileInView={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.8 }}
                            viewport={{ once: true }}
                        >
                            <div className="relative w-full h-96 rounded-2xl overflow-hidden shadow-2xl">
                                <AnimatePresence mode="wait">
                                    <motion.div
                                        key={currentSlide}
                                        className="absolute inset-0 bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center"
                                        initial={{ opacity: 0, scale: 1.1 }}
                                        animate={{ opacity: 1, scale: 1 }}
                                        exit={{ opacity: 0, scale: 0.9 }}
                                        transition={{ duration: 0.5 }}
                                    >
                                        <div className="text-center text-white p-8">
                                            <h3 className="text-2xl font-bold mb-4">{carouselImages[currentSlide].title}</h3>
                                            <p className="text-lg">{carouselImages[currentSlide].description}</p>
                                        </div>
                                    </motion.div>
                                </AnimatePresence>

                                {/* Controles do carrossel */}
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:bg-white/20"
                                    onClick={() => setCurrentSlide((prev) => (prev - 1 + carouselImages.length) % carouselImages.length)}
                                >
                                    <ChevronLeft className="w-6 h-6" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:bg-white/20"
                                    onClick={() => setCurrentSlide((prev) => (prev + 1) % carouselImages.length)}
                                >
                                    <ChevronRight className="w-6 h-6" />
                                </Button>

                                {/* Indicadores */}
                                <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2">
                                    {carouselImages.map((_, index) => (
                                        <button
                                            key={index}
                                            className={`w-3 h-3 rounded-full transition-all ${index === currentSlide ? 'bg-white' : 'bg-white/50'
                                                }`}
                                            onClick={() => setCurrentSlide(index)}
                                        />
                                    ))}
                                </div>
                            </div>
                        </motion.div>

                        {/* Texto principal */}
                        <motion.div
                            className="text-white"
                            initial={{ opacity: 0, x: 50 }}
                            whileInView={{ opacity: 1, x: 0 }}
                            transition={{ duration: 0.8 }}
                            viewport={{ once: true }}
                        >
                            <motion.h2
                                className="text-4xl md:text-6xl font-bold mb-4"
                                initial={{ opacity: 0, y: 30 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.8, delay: 0.2 }}
                                viewport={{ once: true }}
                            >
                                Aprenda com o SpeakNut
                            </motion.h2>
                            <motion.p
                                className="text-2xl md:text-4xl font-black text-yellow-400 mb-8"
                                initial={{ opacity: 0, y: 30 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.8, delay: 0.4 }}
                                viewport={{ once: true }}
                            >
                                ONDE ESTIVER
                            </motion.p>
                        </motion.div>
                    </div>

                    {/* Seção de benefícios */}
                    <motion.div
                        className="max-w-4xl mx-auto"
                        initial={{ opacity: 0, y: 50 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8 }}
                        viewport={{ once: true }}
                    >
                        <Card className="bg-white/10 backdrop-blur-md border-white/20 text-white">
                            <CardContent className="p-8 md:p-12">
                                <motion.h3
                                    className="text-3xl font-bold text-yellow-400 mb-6 text-center"
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.6, delay: 0.2 }}
                                    viewport={{ once: true }}
                                >
                                    Por que escolher o SpeakNut?
                                </motion.h3>

                                <motion.p
                                    className="text-lg md:text-xl mb-8 text-center leading-relaxed"
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.6, delay: 0.4 }}
                                    viewport={{ once: true }}
                                >
                                    Nossa plataforma de aprendizado de idiomas foi criada para ajudar você a estudar de forma prática e divertida.
                                    Com aulas interativas, exercícios personalizados e suporte em tempo real, você aprende no seu ritmo, de qualquer lugar do mundo.
                                </motion.p>

                                <div className="grid md:grid-cols-2 gap-6 mb-8">
                                    {[
                                        { icon: <BookOpen className="w-6 h-6" />, text: "Aulas dinâmicas e personalizadas" },
                                        { icon: <Users className="w-6 h-6" />, text: "Professores nativos disponíveis" },
                                        { icon: <Gamepad2 className="w-6 h-6" />, text: "Exercícios interativos e gamificação" },
                                        { icon: <Globe className="w-6 h-6" />, text: "Estude no computador, celular ou tablet" }
                                    ].map((benefit, index) => (
                                        <motion.div
                                            key={index}
                                            className="flex items-center space-x-3"
                                            initial={{ opacity: 0, x: -20 }}
                                            whileInView={{ opacity: 1, x: 0 }}
                                            transition={{ duration: 0.6, delay: 0.6 + index * 0.1 }}
                                            viewport={{ once: true }}
                                        >
                                            <div className="text-yellow-400">{benefit.icon}</div>
                                            <span className="text-lg">{benefit.text}</span>
                                        </motion.div>
                                    ))}
                                </div>

                                <motion.div
                                    className="text-center"
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.6, delay: 1 }}
                                    viewport={{ once: true }}
                                >
                                    <Button
                                        size="lg"
                                        className="bg-yellow-400 hover:bg-yellow-500 text-black font-bold px-8 py-4 text-lg rounded-full shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300"
                                        onClick={() => scrollToSection('units')}
                                    >
                                        Comece agora gratuitamente 🚀
                                    </Button>
                                </motion.div>
                            </CardContent>
                        </Card>
                    </motion.div>
                </div>

                {/* Mascote flutuante */}
                <motion.div
                    className="hidden lg:block absolute bottom-10 right-10"
                    animate={{
                        y: [0, -15, 0],
                        rotate: [0, 5, -5, 0],
                    }}
                    transition={{
                        duration: 3,
                        repeat: Infinity,
                        ease: "easeInOut"
                    }}
                >
                    <div className="w-32 h-32 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center text-4xl shadow-xl">
                        🐿️
                    </div>
                </motion.div>
            </section>

            {/* Seção 3: Unidades de Aprendizado */}
            <section id="units" className="min-h-screen py-20 bg-gradient-to-br from-indigo-900 to-purple-900 relative overflow-hidden">
                {/* Elementos decorativos */}
                <div className="absolute inset-0">
                    {[...Array(15)].map((_, i) => (
                        <motion.div
                            key={i}
                            className="absolute w-4 h-4 bg-white/10 rounded-full"
                            style={{
                                left: `${Math.random() * 100}%`,
                                top: `${Math.random() * 100}%`,
                            }}
                            animate={{
                                scale: [1, 1.5, 1],
                                opacity: [0.3, 0.8, 0.3],
                            }}
                            transition={{
                                duration: 4 + Math.random() * 2,
                                repeat: Infinity,
                                delay: Math.random() * 2,
                            }}
                        />
                    ))}
                </div>

                <div className="container mx-auto px-4 relative z-10">
                    {/* Mascote pequeno no canto */}
                    <motion.div
                        className="absolute top-8 left-8 w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg"
                        animate={{
                            y: [0, -10, 0],
                        }}
                        transition={{
                            duration: 3,
                            repeat: Infinity,
                            ease: "easeInOut"
                        }}
                    >
                        <span className="text-2xl">🐿️</span>
                    </motion.div>

                    <motion.div
                        className="text-center mb-12"
                        initial={{ opacity: 0, y: 50 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8 }}
                        viewport={{ once: true }}
                    >
                        <h2 className="text-3xl md:text-5xl font-bold text-white mb-4">
                            O aprendizado é{' '}
                            <span className="text-yellow-400">dividido em unidades</span>, cada uma com:
                        </h2>
                        <motion.p
                            className="text-xl text-purple-200"
                            key={currentContentSlide}
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5 }}
                        >
                            {contentSlides[currentContentSlide].subtitle}
                        </motion.p>
                    </motion.div>

                    {/* Indicadores */}
                    <div className="flex justify-center space-x-3 mb-12">
                        {contentSlides.map((_, index) => (
                            <button
                                key={index}
                                className={`w-4 h-4 rounded-full transition-all duration-300 ${index === currentContentSlide ? 'bg-yellow-400 scale-125' : 'bg-white/40'
                                    }`}
                                onClick={() => setCurrentContentSlide(index)}
                            />
                        ))}
                    </div>

                    {/* Conteúdo principal */}
                    <motion.div
                        className="max-w-5xl mx-auto"
                        initial={{ opacity: 0, y: 50 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8 }}
                        viewport={{ once: true }}
                    >
                        <Card className="bg-white/95 backdrop-blur-md border-0 shadow-2xl overflow-hidden">
                            <div className="h-2 bg-gradient-to-r from-purple-600 via-pink-600 to-yellow-400" />
                            <CardContent className="p-0">
                                <AnimatePresence mode="wait">
                                    <motion.div
                                        key={currentContentSlide}
                                        className="grid md:grid-cols-2 gap-8 p-8 md:p-12 items-center min-h-[400px]"
                                        initial={{ opacity: 0, x: 50 }}
                                        animate={{ opacity: 1, x: 0 }}
                                        exit={{ opacity: 0, x: -50 }}
                                        transition={{ duration: 0.5 }}
                                    >
                                        {/* Ícone e conteúdo */}
                                        <div className="text-center md:text-left">
                                            <motion.div
                                                className="flex justify-center md:justify-start mb-6"
                                                whileHover={{ scale: 1.1, rotate: 5 }}
                                                transition={{ type: "spring", stiffness: 400 }}
                                            >
                                                {contentSlides[currentContentSlide].icon}
                                            </motion.div>
                                            <h3 className="text-2xl md:text-3xl font-bold text-gray-800 mb-4">
                                                {contentSlides[currentContentSlide].title}
                                            </h3>
                                            <p className="text-lg text-gray-600 leading-relaxed">
                                                {contentSlides[currentContentSlide].description}
                                            </p>
                                        </div>

                                        {/* Visualização interativa */}
                                        <div className="flex justify-center">
                                            <motion.div
                                                className="w-64 h-64 bg-gradient-to-br from-purple-100 to-pink-100 rounded-2xl flex items-center justify-center shadow-lg"
                                                whileHover={{ scale: 1.05 }}
                                                transition={{ type: "spring", stiffness: 400 }}
                                            >
                                                <motion.div
                                                    className="text-6xl"
                                                    animate={{
                                                        rotate: [0, 10, -10, 0],
                                                    }}
                                                    transition={{
                                                        duration: 2,
                                                        repeat: Infinity,
                                                        ease: "easeInOut"
                                                    }}
                                                >
                                                    {currentContentSlide === 0 && '📚'}
                                                    {currentContentSlide === 1 && '🎮'}
                                                    {currentContentSlide === 2 && '💬'}
                                                </motion.div>
                                            </motion.div>
                                        </div>
                                    </motion.div>
                                </AnimatePresence>
                            </CardContent>
                        </Card>
                    </motion.div>

                    {/* Botão de voltar ao início */}
                    <motion.div
                        className="text-center mt-12"
                        initial={{ opacity: 0, y: 30 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.8 }}
                        viewport={{ once: true }}
                    >
                        <Button
                            variant="outline"
                            className="border-white text-white hover:bg-white hover:text-purple-900 rounded-full px-8 py-3"
                            onClick={() => scrollToSection('welcome')}
                        >
                            ↑ Voltar ao início
                        </Button>
                    </motion.div>
                </div>
            </section>

            {/* Footer */}
            <footer className="bg-purple-950 text-white py-16">
                <div className="container mx-auto px-4">
                    <div className="grid md:grid-cols-3 gap-8">
                        {/* Logo e descrição */}
                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.6 }}
                            viewport={{ once: true }}
                        >
                            <h3 className="text-2xl font-bold mb-4">SpeakNut</h3>
                            <p className="text-purple-200 mb-4">
                                Sua jornada para a fluência começa aqui. Aprenda idiomas de forma divertida e eficaz, onde quer que esteja.
                            </p>
                            <div className="flex space-x-4">
                                {[
                                    { icon: '📘', label: 'Facebook' },
                                    { icon: '🐦', label: 'Twitter' },
                                    { icon: '📷', label: 'Instagram' },
                                    { icon: '💼', label: 'LinkedIn' }
                                ].map((social, index) => (
                                    <motion.button
                                        key={index}
                                        className="w-10 h-10 bg-purple-800 rounded-full flex items-center justify-center hover:bg-purple-700 transition-colors"
                                        whileHover={{ scale: 1.1 }}
                                        whileTap={{ scale: 0.95 }}
                                    >
                                        <span className="text-sm">{social.icon}</span>
                                    </motion.button>
                                ))}
                            </div>
                        </motion.div>

                        {/* Links rápidos */}
                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.6, delay: 0.2 }}
                            viewport={{ once: true }}
                        >
                            <h4 className="text-lg font-semibold mb-4 text-yellow-400">Links Rápidos</h4>
                            <ul className="space-y-2">
                                {['Início', 'Sobre Nós', 'Termos de Serviço', 'Política de Privacidade'].map((link, index) => (
                                    <li key={index}>
                                        <button className="text-purple-200 hover:text-white transition-colors">
                                            {link}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </motion.div>

                        {/* Contato */}
                        <motion.div
                            initial={{ opacity: 0, y: 30 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.6, delay: 0.4 }}
                            viewport={{ once: true }}
                        >
                            <h4 className="text-lg font-semibold mb-4 text-yellow-400">Contato</h4>
                            <div className="space-y-2 text-purple-200">
                                <p>Email: contato@speaknut.com</p>
                                <p>Telefone: (12) 3954-33001</p>
                            </div>
                        </motion.div>
                    </div>

                    <motion.div
                        className="border-t border-purple-800 mt-12 pt-8 text-center text-purple-300"
                        initial={{ opacity: 0 }}
                        whileInView={{ opacity: 1 }}
                        transition={{ duration: 0.6, delay: 0.6 }}
                        viewport={{ once: true }}
                    >
                        <p>&copy; 2025 SpeakNut. Todos os direitos reservados.</p>
                    </motion.div>
                </div>
            </footer>
        </div>
    )
}

export default App

