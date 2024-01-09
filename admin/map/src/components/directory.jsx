import PropTypes from 'prop-types'
import { useEffect, useRef, useState } from 'react'
import { schemaContent } from '../utils/schemaContent'

export default function Directory({ profiles, linkType, pageSize }) {
  const [currentPage, setCurrentPage] = useState(1)
  const totalPages = Math.ceil(profiles.length / pageSize)
  const currentProfiles = profiles.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  )
  const [pageNumbers, setPageNumbers] = useState([])
  const [inputPage, setInputPage] = useState('')
  const directoryComponent = useRef(null)

  useEffect(() => {
    if (totalPages) {
      setPageNumbers(calculatePageNumbers(currentPage, totalPages))
    }
    if (directoryComponent.current) {
      const position = directoryComponent.current.offsetTop
      window.scrollTo({
        top: position,
        behavior: 'smooth'
      })
    }
  }, [currentPage, totalPages])

  const handlePageChange = page => {
    setCurrentPage(page)
  }

  const handlePageInput = e => {
    setInputPage(e.target.value)
  }

  const jumpToPage = () => {
    const pageNumber = Number(inputPage)
    if (
      pageNumber >= 1 &&
      pageNumber <= totalPages &&
      pageNumber !== currentPage
    ) {
      handlePageChange(pageNumber)
    }
    setInputPage('')
  }

  const handleKeyDown = e => {
    if (e.key === 'Enter') {
      jumpToPage()
    }
  }

  function calculatePageNumbers(currentPage, totalPages) {
    const pagesToShow = 5
    let pages = []
    let startPage, endPage

    if (totalPages <= pagesToShow) {
      startPage = 1
      endPage = totalPages
    } else {
      const halfPagesToShow = Math.floor(pagesToShow / 2)
      if (currentPage <= pagesToShow - halfPagesToShow) {
        startPage = 1
        endPage = pagesToShow
      } else if (currentPage + halfPagesToShow >= totalPages) {
        startPage = totalPages - pagesToShow + 1
        endPage = totalPages
      } else {
        startPage = currentPage - halfPagesToShow
        endPage = currentPage + halfPagesToShow
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      pages.push(i)
    }

    return pages
  }

  return (
    <div className="mx-auto max-w-screen-md" ref={directoryComponent}>
      <div className="divide-y divide-gray-300">
        {currentProfiles.map((profile, index) => (
          <div key={index}>
            <div
              dangerouslySetInnerHTML={{
                __html: schemaContent(profile, linkType)
              }}
            />
            <hr className="my-4" />
          </div>
        ))}
      </div>
      {currentProfiles.length > 0 ? (
        <div>
          <div className="my-4 flex flex-row flex-wrap items-center justify-center">
            <button
              className="m-1 rounded bg-gray-500 px-4 py-2 text-white"
              onClick={() => handlePageChange(1)}
            >
              &lt;&lt;
            </button>
            <button
              className="m-1 rounded bg-gray-500 px-4 py-2 text-white"
              onClick={() => handlePageChange(currentPage - 1)}
              disabled={currentPage === 1}
            >
              &lt;
            </button>
            {pageNumbers.map(page => (
              <button
                key={page}
                onClick={() => handlePageChange(page)}
                className={`m-1 rounded px-4 py-2 ${
                  currentPage === page
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-500 text-white'
                }`}
              >
                {page}
              </button>
            ))}
            <button
              className="m-1 rounded bg-gray-500 px-4 py-2 text-white"
              onClick={() => handlePageChange(currentPage + 1)}
              disabled={currentPage === totalPages}
            >
              &gt;
            </button>
            <button
              className="m-1 rounded bg-gray-500 px-4 py-2 text-white"
              onClick={() => handlePageChange(totalPages)}
            >
              &gt;&gt;
            </button>
          </div>
          <div className="my-4 flex flex-row flex-wrap items-center justify-center">
            <input
              type="text"
              placeholder="Go to page..."
              value={inputPage}
              onChange={handlePageInput}
              onKeyDown={handleKeyDown}
              className="m-1 rounded border-2 border-gray-300 px-2 py-1"
            />
            <button
              className="m-1 rounded bg-gray-500 px-2 py-1 text-white"
              onClick={jumpToPage}
            >
              Go
            </button>
            <span className="px-4 py-2">
              Page {currentPage} of {totalPages}
            </span>
          </div>
        </div>
      ) : null}
    </div>
  )
}

Directory.propTypes = {
  profiles: PropTypes.array.isRequired,
  linkType: PropTypes.string.isRequired,
  pageSize: PropTypes.number.isRequired
}
