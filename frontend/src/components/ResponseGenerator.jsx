import React, { useState } from 'react';
import { generateResponse, submitAction } from '../services/api';

const ResponseGenerator = ({ comment, onActionComplete }) => {
    const [isOpen, setIsOpen] = useState(false);
    const [responseType, setResponseType] = useState('Thank You');
    const [language, setLanguage] = useState('English');
    const [generatedText, setGeneratedText] = useState('');
    const [isGenerating, setIsGenerating] = useState(false);
    const [isConfirmed, setIsConfirmed] = useState(false);
    const [action, setAction] = useState('Publish');
    const [isSubmitted, setIsSubmitted] = useState(false);
    const handleGenerate = async () => {
        setIsGenerating(true);
        setGeneratedText(''); // Clear previous
        try {
            const data = await generateResponse(comment.id, responseType, language);
            setGeneratedText(data.response);
        } catch (error) {
            console.error(error);
            setGeneratedText('Error generating response.');
        } finally {
            setIsGenerating(false);
        }
    };

    const handleSubmit = async () => {
        if (!isConfirmed) return;
        setIsSubmitted(true);

        try {
            await submitAction(comment.id, action.toLowerCase(), generatedText);
            setIsOpen(false);
            if (onActionComplete) onActionComplete();
        } catch (error) {
            alert('Failed to submit action: ' + error.message);
        } finally {
            setIsSubmitted(false);
        }
    };

    if (comment.response_text) {
        return (
            <div className="response-summary">
                <div className="saved-response">
                    <strong>Response:</strong>
                    <p>{comment.response_text}</p>
                </div>
            </div>
        );
    }

    if (!isOpen) {
        return (
            <button className="response-btn" onClick={() => setIsOpen(true)}>
                Draft Response
            </button>
        );
    }

    return (
        <div className="response-generator">
            <div className="controls">
                <select
                    value={responseType}
                    onChange={(e) => setResponseType(e.target.value)}
                    className="response-type-select"
                >
                    <option value="Custom">Custom Response</option>
                    <option value="Thank You">Thank You Response</option>
                    <option value="Redirect">Redirect to Team</option>
                </select>
                <select
                    value={language}
                    onChange={(e) => setLanguage(e.target.value)}
                    className="response-type-select"
                >
                    <option value="German">German</option>
                    <option value="English">English</option>
                    <option value="Serbo-Croatian">Serbo-Croatian</option>
                    <option value="Turkish">Turkish</option>
                </select>
                <button
                    className="generate-btn"
                    onClick={handleGenerate}
                    disabled={isGenerating}
                >
                    {isGenerating ? 'Generating...' : 'Generate Draft'}
                </button>
                <button className="close-btn" onClick={() => setIsOpen(false)}>×</button>
            </div>

            <div className={`response-area ${isGenerating ? 'generating' : ''}`}>
                <textarea
                    value={generatedText}
                    onChange={(e) => setGeneratedText(e.target.value)}
                    placeholder="Generated response will appear here..."
                    disabled={isGenerating}
                />
            </div>

            {generatedText && (
                <div className="submission-controls">
                    <div className="confirmation">
                        <label>
                            <input
                                type="checkbox"
                                checked={isConfirmed}
                                onChange={(e) => setIsConfirmed(e.target.checked)}
                            />
                            I have read the response and confirm it is appropriate.
                        </label>
                    </div>
                    <div className="actions">
                        <button
                            className="submit-btn"
                            onClick={handleSubmit}
                            disabled={!isConfirmed || isSubmitted}
                        >
                            {isSubmitted ? 'Submitting...' : 'Submit Action'}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ResponseGenerator;
