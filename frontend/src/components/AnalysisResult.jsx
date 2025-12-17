import React, { useState } from 'react';

const AnalysisResult = ({ analysis }) => {
    const [expanded, setExpanded] = useState(false);

    if (!analysis) return null;

    const {
        detected_language,
        topic,
        sentiment,
        urgency,
        requires_response,
        inappropriate_content,
        explanation_json
    } = analysis;

    // If analysis hasn't run yet, these fields will be null
    if (!topic || !sentiment) return null;

    // Parse explanation if it's a JSON string
    let explanation = explanation_json;
    try {
        if (typeof explanation_json === 'string' && (explanation_json.startsWith('{') || explanation_json.startsWith('"'))) {
            explanation = JSON.parse(explanation_json);
        }
    } catch (e) {
        // ignore
    }

    // Determine if content is flagged (anything other than "None")
    const isFlagged = inappropriate_content && inappropriate_content !== 'None';
    const isHighUrgency = urgency === 'High';
    const needsResponse = requires_response === 'Yes';

    return (
        <div className="analysis-result">
            <div className="tags">
                {/* Language indicator */}
                {detected_language && (
                    <span className="tag language">{detected_language.toUpperCase()}</span>
                )}

                {/* Topic */}
                <span className="tag topic">{topic}</span>

                {/* Sentiment */}
                <span className={`tag sentiment ${sentiment.toLowerCase()}`}>{sentiment}</span>

                {/* Urgency - only show if High or Medium */}
                {(urgency === 'High' || urgency === 'Medium') && (
                    <span className={`tag urgency ${urgency.toLowerCase()}`}>{urgency} Priority</span>
                )}

                {/* Needs Response indicator */}
                {needsResponse && (
                    <span className="tag needs-response">Needs Response</span>
                )}

                {/* Inappropriate content flag */}
                {isFlagged && (
                    <span className="tag flagged">{inappropriate_content}</span>
                )}
            </div>

            <button
                className="toggle-explanation"
                onClick={() => setExpanded(!expanded)}
            >
                {expanded ? 'Hide Explanation' : 'Show Explanation'}
            </button>

            {expanded && (
                <div className="explanation">
                    <strong>Analysis Reasoning:</strong>
                    <p>{explanation}</p>
                </div>
            )}
        </div>
    );
};

export default AnalysisResult;
