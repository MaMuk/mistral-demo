
import React, { useEffect, useState } from 'react';
import { fetchComments, analyzeComments, resetAnalysis } from './services/api';
import CommentList from './components/CommentList';
import './index.css';

function App() {
  const [comments, setComments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [analyzingAll, setAnalyzingAll] = useState(false);
  const [analyzingIds, setAnalyzingIds] = useState(new Set());
  const [analysisProgress, setAnalysisProgress] = useState({ current: 0, total: 0 });
  const [error, setError] = useState(null);

  useEffect(() => {
    loadComments();
  }, []);

  const loadComments = async () => {
    try {
      const data = await fetchComments();
      setComments(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  // Sequential analysis - one comment at a time to avoid timeouts
  const handleAnalyzeAll = async () => {
    const unanalyzed = comments.filter(c => !c.topic);
    if (unanalyzed.length === 0) return;

    setAnalyzingAll(true);
    setAnalysisProgress({ current: 0, total: unanalyzed.length });

    for (let i = 0; i < unanalyzed.length; i++) {
      const comment = unanalyzed[i];
      setAnalysisProgress({ current: i + 1, total: unanalyzed.length });
      setAnalyzingIds(prev => new Set(prev).add(comment.id));

      try {
        const data = await analyzeComments(comment.id);
        setComments(data);
      } catch (err) {
        setError(`Error analyzing comment ${comment.id}: ${err.message}`);
        // Continue with next comment despite error
      } finally {
        setAnalyzingIds(prev => {
          const next = new Set(prev);
          next.delete(comment.id);
          return next;
        });
      }
    }

    setAnalyzingAll(false);
    setAnalysisProgress({ current: 0, total: 0 });
  };

  const handleAnalyzeSingle = async (id) => {
    setAnalyzingIds(prev => new Set(prev).add(id));
    try {
      const data = await analyzeComments(id);
      setComments(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setAnalyzingIds(prev => {
        const next = new Set(prev);
        next.delete(id);
        return next;
      });
    }
  };

  const handleReset = async () => {
    if (!window.confirm('Are you sure you want to delete all analysis data? This cannot be undone.')) {
      return;
    }
    setLoading(true);
    try {
      const data = await resetAnalysis();
      setComments(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="app-container">
      <header className="app-header">
        <p>Local LLM Analysis (Mistral)</p>
      </header>

      <main>
        {error && (
          <div className="error-banner">
            {error}
            <button className="dismiss-error" onClick={() => setError(null)}>×</button>
          </div>
        )}
        {loading ? (
          <div className="loading">Loading comments...</div>
        ) : (
          <CommentList
            comments={comments}
            onAnalyzeAll={handleAnalyzeAll}
            onAnalyzeSingle={handleAnalyzeSingle}
            isAnalyzingAll={analyzingAll}
            analyzingIds={analyzingIds}
            analysisProgress={analysisProgress}
            onRefresh={loadComments}
          />
        )}

        <div className="reset-section">
          <button className="reset-btn" onClick={handleReset}>
            Reset Demo (Clear Analysis and Responses)
          </button>
        </div>
      </main>
    </div>
  );
}

export default App;

