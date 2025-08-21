#!/bin/bash
# Ollama Management Script for Budget App

echo "🤖 Ollama AI Service Manager for Budget App"
echo "=========================================="

case "$1" in
    "start")
        echo "🚀 Starting Ollama service..."
        ollama serve &
        echo "Ollama is now running in the background"
        ;;
    "stop")
        echo "🛑 Stopping Ollama service..."
        pkill ollama
        echo "Ollama stopped"
        ;;
    "status")
        echo "📊 Checking Ollama status..."
        if pgrep ollama > /dev/null; then
            echo "✅ Ollama is running"
            curl -s http://localhost:11434/api/tags 2>/dev/null | grep -q "llama3.2:1b" && echo "✅ Budget AI model (llama3.2:1b) is ready"
        else
            echo "❌ Ollama is not running"
        fi
        ;;
    "test")
        echo "🧪 Testing AI with financial question..."
        ollama run llama3.2:1b "Give me 3 quick money-saving tips"
        ;;
    "models")
        echo "📋 Installed models:"
        ollama list
        ;;
    "upgrade")
        echo "⬆️ Upgrading to a better model..."
        ollama pull llama3.2:3b
        echo "You can now use llama3.2:3b for better responses (edit ai_service.php)"
        ;;
    *)
        echo "Usage: $0 {start|stop|status|test|models|upgrade}"
        echo ""
        echo "Commands:"
        echo "  start   - Start Ollama service"
        echo "  stop    - Stop Ollama service" 
        echo "  status  - Check if Ollama is running"
        echo "  test    - Test AI with a sample question"
        echo "  models  - List installed AI models"
        echo "  upgrade - Install larger 3B model (better quality)"
        ;;
esac
