import React, { useState } from 'react';
import AnalysisResult from './AnalysisResult';
import ResponseGenerator from './ResponseGenerator';
import { translateComment, submitAction } from '../services/api';

const CommentList = ({ comments, onAnalyzeAll, onAnalyzeSingle, isAnalyzingAll, analyzingIds, analysisProgress, onRefresh }) => {
    const [translatingIds, setTranslatingIds] = useState(new Set());

    const handleTranslate = async (id) => {
        setTranslatingIds(prev => new Set(prev).add(id));
        try {
            await translateComment(id);
            if (onRefresh) onRefresh();
        } catch (error) {
            console.error(error);
        } finally {
            setTranslatingIds(prev => {
                const next = new Set(prev);
                next.delete(id);
                return next;
            });
        }
    };

    const unanalyzedCount = comments.filter(c => !c.topic).length;
    const progressPercent = analysisProgress.total > 0
        ? Math.round((analysisProgress.current / analysisProgress.total) * 100)
        : 0;

    return (
        <div className="comment-list-container">
            <div className="header">
                <h2>Feedback Comments ({comments.length})</h2>
                <div className="analyze-controls">
                    {isAnalyzingAll && analysisProgress.total > 0 && (
                        <div className="progress-indicator">
                            <div className="progress-bar">
                                <div
                                    className="progress-fill"
                                    style={{ width: `${progressPercent}%` }}
                                />
                            </div>
                            <span className="progress-text">
                                {analysisProgress.current}/{analysisProgress.total}
                            </span>
                        </div>
                    )}
                    <button
                        className="analyze-btn"
                        onClick={onAnalyzeAll}
                        disabled={isAnalyzingAll || unanalyzedCount === 0}
                    >
                        {isAnalyzingAll
                            ? `Analyzing ${analysisProgress.current}/${analysisProgress.total}...`
                            : unanalyzedCount > 0
                                ? `Analyze All (${unanalyzedCount} pending)`
                                : 'All Analyzed ✓'}
                    </button>
                </div>
            </div>

            <div className="comment-list">
                {comments.map((comment) => {
                    const isAnalyzing = analyzingIds.has(comment.id);
                    const isAnalyzed = !!comment.topic;

                    return (
                        <div key={comment.id} className={`comment-card ${isAnalyzing ? 'analyzing' : ''}`}>
                            <div className="card-header">
                                <span className={`status-badge ${isAnalyzed ? 'done' : 'pending'}`}>
                                    {isAnalyzed ? 'Analyzed' : 'Not Analyzed'}
                                </span>
                                <StatusSelector
                                    comment={comment}
                                    onStatusChange={onRefresh}
                                />
                                {!isAnalyzed && (
                                    <button
                                        className="analyze-single-btn"
                                        onClick={() => onAnalyzeSingle(comment.id)}
                                        disabled={isAnalyzing || isAnalyzingAll}
                                    >
                                        {isAnalyzing ? 'Analyzing...' : 'Analyze'}
                                    </button>
                                )}
                                {isAnalyzed && isAnalyzing && (
                                    <button
                                        className="analyze-single-btn"
                                        disabled={true}
                                    >
                                        Re-analyzing...
                                    </button>
                                )}
                            </div>
                            <div className="comment-text">
                                "{comment.text}"
                                {comment.translated_text && (
                                    <div className="translated-text">
                                        <strong>Translated (German):</strong> {comment.translated_text}
                                    </div>
                                )}
                                {!comment.translated_text && (
                                    <button
                                        className="translate-btn"
                                        onClick={() => handleTranslate(comment.id)}
                                        disabled={translatingIds.has(comment.id)}
                                    >
                                        {translatingIds.has(comment.id) ? 'Translating...' : 'Translate to German'}
                                    </button>
                                )}
                            </div>
                            <AnalysisResult analysis={comment} />
                            <div className="response-generator-container">
                                <ResponseGenerator comment={comment} onActionComplete={onRefresh} />
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

const StatusSelector = ({ comment, onStatusChange }) => {
    const [status, setStatus] = useState(comment.status || 'unreviewed');
    const [isSaving, setIsSaving] = useState(false);
    const [saved, setSaved] = useState(false);

    const handleChange = async (newStatus) => {
        setStatus(newStatus);
        setIsSaving(true);
        setSaved(false);
        try {
            await submitAction(comment.id, newStatus.toLowerCase(), null);
            setSaved(true);
            if (onStatusChange) onStatusChange();
            setTimeout(() => setSaved(false), 2000);
        } catch (error) {
            console.error('Failed to update status:', error);
            alert('Failed to update status');
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="status-selector-container">
            <select
                value={status}
                onChange={(e) => handleChange(e.target.value)}
                disabled={isSaving}
                className={`status-select-header ${status}`}
            >
                <option value="unreviewed">Unreviewed</option>
                <option value="published">Published</option>
                <option value="blocked">Blocked</option>
            </select>
            {isSaving && <span className="status-saving-indicator">...</span>}
            {saved && <span className="status-saved-indicator">✓</span>}
        </div>
    );
};

export default CommentList;
