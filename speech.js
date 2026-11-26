/**
 * Mero Sahayata - Speech-to-Text & Text-to-Speech Accessibility Engine
 * Enables voice searching and spoken procedural guidance for low-literacy users.
 */

class SpeechAssistant {
    constructor() {
        this.recognition = null;
        this.synth = window.speechSynthesis || null;
        this.isRecording = false;
        this.currentUtterance = null;
        this.initRecognition();
    }

    /**
     * Initialize Web Speech Recognition
     */
    initRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            console.warn("Speech Recognition API not supported in this browser.");
            return;
        }

        this.recognition = new SpeechRecognition();
        this.recognition.continuous = false;
        this.recognition.interimResults = false;
        this.recognition.maxAlternatives = 1;

        // Default to Nepali (ne-NP) with fallback
        this.recognition.lang = 'ne-NP';

        this.recognition.onstart = () => {
            this.isRecording = true;
            this.updateVoiceButtonUI(true);
            const statusEl = document.getElementById('voiceStatus');
            if (statusEl) statusEl.innerText = "🎙️ सुन्दैछु... कृपया सेवाको नाम बोल्नुहोस् (Listening...)";
        };

        this.recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            console.log("Voice transcript recognized:", transcript);
            const searchField = document.getElementById('searchInput');
            if (searchField) {
                searchField.value = transcript;
                // Auto trigger search with voice type
                if (window.triggerSearch) {
                    window.triggerSearch(transcript, 'voice');
                }
            }
            const statusEl = document.getElementById('voiceStatus');
            if (statusEl) statusEl.innerText = `तपाईंले भन्नुभयो: "${transcript}"`;
        };

        this.recognition.onerror = (event) => {
            console.error("Speech recognition error:", event.error);
            this.isRecording = false;
            this.updateVoiceButtonUI(false);
            const statusEl = document.getElementById('voiceStatus');
            if (statusEl) {
                if (event.error === 'not-allowed') {
                    statusEl.innerText = "⚠️ माइकको अनुमति अस्वीकृत भयो (Microphone access denied).";
                } else {
                    statusEl.innerText = "पुनः प्रयास गर्नुहोस् (Please try speaking again).";
                }
            }
        };

        this.recognition.onend = () => {
            this.isRecording = false;
            this.updateVoiceButtonUI(false);
        };
    }

    /**
     * Start/Stop voice recognition
     */
    toggleListening() {
        if (!this.recognition) {
            alert("तपाईंको ब्राउजरमा भ्वाइस इनपुट समर्थित छैन। कृपया Chrome वा Edge प्रयोग गर्नुहोस्।\n(Speech Recognition is not supported in this browser. Please use Chrome/Edge.)");
            return;
        }

        if (this.isRecording) {
            this.recognition.stop();
        } else {
            try {
                this.recognition.start();
            } catch (err) {
                console.warn("Recognition already active", err);
            }
        }
    }

    updateVoiceButtonUI(recording) {
        const btn = document.getElementById('btnVoiceSearch');
        if (btn) {
            if (recording) {
                btn.classList.add('recording');
                btn.setAttribute('aria-label', 'Recording in progress');
            } else {
                btn.classList.remove('recording');
                btn.setAttribute('aria-label', 'Start voice search');
            }
        }
    }

    /**
     * Text-to-Speech: Read aloud Nepali/English guidance
     */
    speak(text, lang = 'ne-NP') {
        if (!this.synth) {
            alert("Text-to-Speech not supported.");
            return;
        }

        // Stop any ongoing speech
        if (this.synth.speaking) {
            this.synth.cancel();
            this.updateAudioButtonUI(false);
            return;
        }

        if (!text || text.trim() === '') return;

        this.currentUtterance = new SpeechSynthesisUtterance(text);
        this.currentUtterance.lang = lang;
        this.currentUtterance.rate = 0.95; // Slightly slower for clarity
        this.currentUtterance.pitch = 1.0;

        // Try to pick suitable voice
        const voices = this.synth.getVoices();
        const nepaliOrHindiVoice = voices.find(v => v.lang.includes('ne') || v.lang.includes('hi'));
        if (nepaliOrHindiVoice) {
            this.currentUtterance.voice = nepaliOrHindiVoice;
        }

        this.currentUtterance.onstart = () => {
            this.updateAudioButtonUI(true);
        };

        this.currentUtterance.onend = () => {
            this.updateAudioButtonUI(false);
        };

        this.currentUtterance.onerror = (e) => {
            console.error("TTS Error:", e);
            this.updateAudioButtonUI(false);
        };

        this.synth.speak(this.currentUtterance);
    }

    stopSpeech() {
        if (this.synth && this.synth.speaking) {
            this.synth.cancel();
            this.updateAudioButtonUI(false);
        }
    }

    updateAudioButtonUI(playing) {
        const btn = document.getElementById('btnAudioSpeak');
        if (btn) {
            if (playing) {
                btn.classList.add('playing');
                btn.innerHTML = `<i class="bi bi-stop-circle"></i> <span data-np="आवाज रोक्नुहोस्" data-en="Stop Audio">आवाज रोक्नुहोस्</span>`;
            } else {
                btn.classList.remove('playing');
                btn.innerHTML = `<i class="bi bi-volume-up"></i> <span data-np="निर्देशन सुन्नुहोस्" data-en="Listen Guidance">निर्देशन सुन्नुहोस् (Listen)</span>`;
            }
        }
    }
}

// Global instance
window.speechHelper = new SpeechAssistant();
