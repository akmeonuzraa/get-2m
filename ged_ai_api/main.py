from fastapi import FastAPI
from ged_ai_api.routers.report_summary import router as report_router
from ged_ai_api.routers.classify import router as classify_router
from ged_ai_api.routers.search import router as search_router
from ged_ai_api.routers.summarize import router as summarize_router

app = FastAPI(title="ged-ai-api")

app.include_router(report_router)
app.include_router(classify_router)
app.include_router(search_router)
app.include_router(summarize_router)

@app.get("/health")
def health():
    return {"status": "ok"}
