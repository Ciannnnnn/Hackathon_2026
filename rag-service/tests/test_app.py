from __future__ import annotations

import io
import unittest
from unittest.mock import patch

from app import create_app
from extraction import PageText, chunk_pages


class RagServiceTest(unittest.TestCase):
    def setUp(self):
        self.client = create_app().test_client()

    def test_health_endpoint(self):
        response = self.client.get("/health")

        self.assertEqual(200, response.status_code)
        self.assertEqual("ok", response.get_json()["status"])

    def test_extract_requires_a_pdf(self):
        response = self.client.post("/extract")

        self.assertEqual(422, response.status_code)
        self.assertIn("required", response.get_json()["message"])

    @patch("app.extract_pdf")
    def test_extract_returns_page_aware_chunks(self, extractor):
        extractor.return_value = {
            "page_count": 1,
            "character_count": 18,
            "chunks": [
                {
                    "chunk_index": 0,
                    "page_number": 1,
                    "content": "Normalized content",
                    "token_count": 2,
                }
            ],
        }

        response = self.client.post(
            "/extract",
            data={"file": (io.BytesIO(b"%PDF-test"), "lesson.pdf", "application/pdf")},
            content_type="multipart/form-data",
        )

        self.assertEqual(200, response.status_code)
        self.assertEqual(1, response.get_json()["chunks"][0]["page_number"])

    def test_chunker_preserves_page_numbers_and_overlap(self):
        chunks = chunk_pages(
            [PageText(3, "Database normalization " * 40)],
            chunk_size=220,
            overlap=40,
        )

        self.assertGreater(len(chunks), 1)
        self.assertTrue(all(chunk["page_number"] == 3 for chunk in chunks))
        self.assertEqual(list(range(len(chunks))), [chunk["chunk_index"] for chunk in chunks])


if __name__ == "__main__":
    unittest.main()
