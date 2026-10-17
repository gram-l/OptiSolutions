"""Read-only audit of cached FiReCS data and the legacy split."""
from pathlib import Path
import json
import pandas as pd

if __name__ == '__main__':
    root = Path.home() / '.cache/huggingface/hub/datasets--ccosme--FiReCS/snapshots'
    snapshot = next(path for path in sorted(root.iterdir()) if (path / 'FiReCS_train_set.csv').exists())
    frames = []
    for split in ['train', 'test']:
        frame = pd.read_csv(snapshot / f'FiReCS_{split}_set.csv')
        frame['source_split'] = split
        frames.append(frame)
    data = pd.concat(frames, ignore_index=True)
    print(json.dumps({'snapshot': str(snapshot), 'rows': len(data), 'columns': list(data.columns),
        'labels': data['label'].value_counts().to_dict(), 'blank': int(data['review'].isna().sum()),
        'exact_duplicate_rows': int(data.duplicated('review').sum()),
        'conflicting_exact_texts': int((data.groupby('review')['label'].nunique() > 1).sum())}, indent=2))
