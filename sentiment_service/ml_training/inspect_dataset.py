from datasets import load_dataset

# Load the FiReCS dataset from Hugging Face
dataset = load_dataset("ccosme/FiReCS")

# Print the overall structure (splits available: train/test/etc.)
print("Dataset structure")
print(dataset)

# Look at the first split (usually 'train')
first_split = list(dataset.keys())[0]
print(f"\n=== Columns in '{first_split}' split ===")
print(dataset[first_split].column_names)

# Print the label mapping (what integer corresponds to Positive/Neutral/Negative)
print("\n=== Label feature info ===")
print(dataset[first_split].features)

# Show 5 example rows so we can see the actual text + label format
print("\n=== Sample rows ===")
for i in range(5):
    print(dataset[first_split][i])