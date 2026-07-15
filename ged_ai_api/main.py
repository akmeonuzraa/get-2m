from fastapi import FastAPI
from ged_ai_api.routers.report_summary import router as report_router

app = FastAPI(title="ged-ai-api")

app.include_router(report_router)

@app.get("/health")
def health():
    return {"status": "ok"}
