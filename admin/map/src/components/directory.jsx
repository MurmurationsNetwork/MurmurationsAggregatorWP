import { iterateObject } from '../utils/iterateObject'
import PropTypes from 'prop-types'
import { useEffect, useState } from 'react'

export default function Directory({ profiles, linkType, pageSize }) {
  const pagesToShow = 5
  const [currentPage, setCurrentPage] = useState(1)
  const totalPages = Math.ceil(profiles.length / pageSize)
  const currentProfiles = profiles.slice(
    (currentPage - 1) * pageSize,
    currentPage * pageSize
  )
  const [pageNumbers, setPageNumbers] = useState([])

  useEffect(() => {
    if (totalPages) {
      setPageNumbers(calculatePageNumbers(currentPage, totalPages))
    }
  }, [currentPage, totalPages])

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
    <div className="max-w-screen-md mx-auto">
      <div className="divide-y divide-gray-300">
        {currentProfiles.map((profile, index) => (
          <div key={index}>
            <li key={profile.id} className="py-4 dir-item">
              {profile.profile_data.image && (
                <img
                  src={profile.profile_data.image}
                  alt="Profile Logo"
                  className="mb-4 max-h-16"
                  onError={e => {
                    e.target.style.display = 'none'
                  }}
                />
              )}
              <p className="text-lg font-bold mb-2">{profile.name}</p>
              <div className="space-y-2">
                <p className="text-sm truncate">
                  {linkType === 'wp' ? (
                    <a href={profile.post_url}>More...</a>
                  ) : (
                    <span>
                      <a
                        href={profile.profile_data.primary_url}
                        target="_blank"
                        rel="noreferrer"
                      >
                        {profile.profile_data.primary_url}
                      </a>
                    </span>
                  )}
                </p>
                {Object.entries(iterateObject(profile.profile_data)).map(
                  ([key, value]) => (
                    <p key={key} className="text-sm truncate">
                      {key}: {value}
                    </p>
                  )
                )}
              </div>
            </li>
            <hr className="my-4" />
          </div>
        ))}
      </div>
      <div className="flex flex-row flex-wrap items-center justify-center my-4">
        <button
          className="px-4 py-2 m-1 rounded bg-white"
          onClick={() => setCurrentPage(1)}
        >
          &lt;&lt;
        </button>
        <button
          className="px-4 py-2 m-1 rounded bg-white"
          onClick={() => setCurrentPage(currentPage - 1)}
          disabled={currentPage === 1}
        >
          &lt;
        </button>
        {pageNumbers.map(page => (
          <button
            key={page}
            onClick={() => setCurrentPage(page)}
            className={`px-4 py-2 m-1 rounded ${
              currentPage === page ? 'bg-blue-500 text-white' : 'bg-white'
            }`}
          >
            {page}
          </button>
        ))}
        <button
          className="px-4 py-2 m-1 rounded bg-white"
          onClick={() => setCurrentPage(currentPage + 1)}
          disabled={currentPage === totalPages}
        >
          &gt;
        </button>
        <button
          className="px-4 py-2 m-1 rounded bg-white"
          onClick={() => setCurrentPage(totalPages)}
        >
          &gt;&gt;
        </button>
      </div>
    </div>
  )
}

Directory.propTypes = {
  profiles: PropTypes.array.isRequired,
  linkType: PropTypes.string.isRequired,
  pageSize: PropTypes.number.isRequired
}
