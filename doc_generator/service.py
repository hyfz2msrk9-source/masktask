from doc_generator.prompt import create_doc_prompt
from doc_generator.exporter import save_as_markdown

class DocGeneratorService:
    def __init__(self):
        self.retriever = None
        self.llm = None

    def initialize(self, vectorstore, llm):
        """RAG側で読み込んだ共通のvectorstoreとllmをセットアップ"""
        self.retriever = vectorstore.as_retriever(search_kwargs={"k": 5})
        self.llm = llm

    def create_document(self, query: str) -> dict:
        # 1. 検索
        docs = self.retriever.get_relevant_documents(query)
        context = "\n---\n".join([doc.page_content for doc in docs]) if docs else "参照情報なし"

        # 2. プロンプト生成 & LLM呼び出し
        prompt = create_doc_prompt(rag_context=context, user_query=query)
        response = self.llm.invoke(prompt)
        generated_md = response.content

        # 3. ファイル保存
        filepath = save_as_markdown(generated_md)

        return {
            "status": "success",
            "filepath": filepath,
            "content": generated_md,
            "sources_count": len(docs)
        }