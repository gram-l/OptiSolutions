"""Package the completed tables, interpretations, and raw evaluation files."""
import argparse
import hashlib
import json
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET


def main(directory):
    content = json.loads((directory / 'report_content.json').read_text(encoding='utf-8'))
    assert len(content['sections']) == 8
    ns = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
    with zipfile.ZipFile(directory / 'Sentiment_Model_Comparison.docx') as archive:
        xml = ET.fromstring(archive.read('word/document.xml'))
        assert not xml.findall('.//w:pBdr', ns)
        tables = xml.findall('.//w:body/w:tbl', ns)
        assert len(tables) == 8
        for table, section in zip(tables, content['sections']):
            actual_rows = []
            for row in table.findall('w:tr', ns):
                actual_rows.append([''.join(cell.itertext()) for cell in row.findall('w:tc', ns)])
            expected_rows = [section['headers']] + [[str(value) for value in row] for row in section['rows']]
            assert actual_rows == expected_rows, f"DOCX values differ in Table 4-{section['number']}"
    for section in content['sections']:
        (directory / f"table_4_{section['number']}_interpretation.txt").write_text(section['interpretation'] + '\n', encoding='utf-8')
    (directory / 'README.txt').write_text(
        'SENTIMENT MODEL EVALUATION RESULTS\n\n'
        'The Word report and Markdown file contain all eight tables and their interpretations.\n'
        'Each table also has a CSV and a separate interpretation text file.\n'
        'comparison.json contains unrounded metrics, selected settings, runtime versions and methodology.\n'
        'The prediction, confusion matrix, classification report, cross-validation and split CSVs retain the evaluation evidence.\n'
        'verification.json records independent numerical checks; files_sha256.json records file checksums.\n\n'
        'Three classifiers, identical TF-IDF features, 8,374 cleaned training reviews and 2,098 held-out reviews.\n'
        'Eight parameter settings per classifier and five identical training-only validation folds.\n'
        'The held-out reviews were previously evaluated, not newly collected independent clinic feedback.\n'
        'The active application model and VPS were not changed. Model pickle files and layout-preview images are excluded from this archive.\n\n'
        'Source: FiReCS by ccosme, CC BY 4.0, https://huggingface.co/datasets/ccosme/FiReCS\n'
        'Snapshot: 4da2863e53e7260ab6f2dcd2089af5c112d25c80\n'
        'Experiment implementation is saved in sentiment_service/ml_training/compare_models.py in the project.\n', encoding='utf-8')
    files = sorted(path for path in directory.iterdir() if path.is_file() and path.suffix in {'.csv', '.json', '.md', '.txt', '.docx'} and path.name != 'files_sha256.json')
    hashes = {path.name: hashlib.sha256(path.read_bytes()).hexdigest() for path in files}
    checksum_file = directory / 'files_sha256.json'
    checksum_file.write_text(json.dumps(hashes, indent=2), encoding='utf-8')
    files.append(checksum_file)
    output = directory / 'Evaluation_Results.zip'
    with zipfile.ZipFile(output, 'x', compression=zipfile.ZIP_DEFLATED) as archive:
        for path in files:
            archive.write(path, path.name)
    with zipfile.ZipFile(output) as archive:
        assert archive.testzip() is None
    print(f'Verified all eight DOCX tables against the results; packaged {len(files)} files: {output}')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('run_directory', type=Path)
    main(parser.parse_args().run_directory)
